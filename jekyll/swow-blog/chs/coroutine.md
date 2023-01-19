---
layout: page
title: Swow 协程从入门到重新入门 <Swow 从零开始系列 - 第一弹 >
parent: Swow Blog CHS
nav_order: 1001
---

# Swow 协程从入门到重新入门

> 以下内容基于 Swow v1.1.0 版本编写

从零开始系列致力于让读者能够从零开始了解 Swow 的基本食用方法、设计理念、最佳实践乃至底层原理等。Swow 的设计更多的是博众家之所长，而非生搬硬造，且最小核心设计使得 Swow 尽可能地贴近原生系统调用。因此读者在文章中所得，并不会局限在 Swow 或 PHP 的领域之中，且它可以用 PHP 代码帮助 PHP 开发者理解系统原理，继而纵览编程的大千世界。

## 协程从入门到重新入门

如果你还没有太多的 Swow 协程开发经验，本篇文章可以帮助你从零开始认识 Swow 中的协程；如果你先前已经有了一些协程开发经验（如 Swoole、Golang 等），那就更好了，本篇文章可以帮助你重新认识在 Swow 中面向对象的协程是什么样的，以及其中一些细节设计、其背后的理念和一些微创新。当然，由于只是入门篇，本篇中的内容仍然只是冰山一角，如果你想要完全掌握协程（广义的、不单指 Swow 的协程），还需要大量的概念学习与实践（以及关注后续更多的文章发布）。

## 协程的创建

首先，Swow 的协程是面向对象的，所以我们可以这样创建一个待运行的协程：

```php
use Swow\Coroutine;

$coroutine = new Coroutine(static function (): void {
    echo "Hello Swow\n";
});
```

这样创建出来的协程并不会被运行，而是只进行了内存的申请。

> 顺带一提的，我们也可以以此来实现一个「协程池」，提前占有一些内存资源，确保有足够的协程可用，而动态创建的协程，可能会在程序内存资源紧张时创建失败。**当然，通常我们不必做到这种程度。**

## 协程的观测

通过 `var_dump` 打印协程对象，我们又可以看到这样的输出：

```php
var_dump($coroutine);
```

```
object(Swow\Coroutine)#2 (4) {
  ["id"]=>
  int(2)
  ["state"]=>
  string(7) "waiting"
  ["switches"]=>
  int(0)
  ["elapsed"]=>
  string(3) "0ms"
}
```

从输出我们可以得到一些协程状态的信息，如：

协程的 id 是2，状态是等待中，切换次数是0，运行了0毫秒（即没有运行）。

通过 `resume()` 方法，我们可以唤醒这个协程：

```php
$coroutine->resume();
```

协程中的PHP代码被执行，于是我们就看到了下述信息：

```
Hello Swow
```

这时候我们再通过 `var_dump($coroutine);` 去打印协程的状态，我们得到以下内容：

```php
object(Swow\Coroutine)#2 (4) {
  ["id"]=>
  int(2)
  ["state"]=>
  string(4) "dead"
  ["switches"]=>
  int(1)
  ["elapsed"]=>
  string(3) "0ms"
}
```

可以看到协程已经运行完了所有的代码并进入`dead`状态，共经历一次协程切换。

## 协程的三个状态

要完全看懂输出的内容，我们先要了解协程的状态有哪些。

Swow 协程一共只有三个状态，`waiting`、`running`、`dead`。

`waiting` 代表协程处于等待状态，此时协程可能刚被创建出来但还没有被唤醒，也可能是由于 I/O操作 让出了，等待被 I/O 事件唤醒。

`running` 代表协程正在运行。

`dead` 代表协程从运行的函数中返回，且析构函数也已经运行完毕，即已经完全退出。

### 细节解释

那么如何区分 `waiting` 状态的协程到底是刚创建完在等待被运行还是运行到一半在等待 I/O 呢？其实多数时候我们并不需要关心这个问题，因为本质上这个状态下的协程都是「等待被唤醒」。

我们可以用以下代码来描述为何两者没有区别：

```php
$coroutine = new Coroutine(static function(): void {
    // do something here
});
$coroutine->resume();
```
```php
$coroutine = Coroutine::run(static function(): void {
    Coroutine::yield(); // waiting for resuming...
    // do something here
});
$coroutine->resume();
```

其实一个刚创建出来的协程，可以等价地视为协程运行后立即就挂起了，两者的效果是一致的，只是后者比前者多了一次调度。

抛开具体实现，在抽象逻辑上，我们完全可以将两者等价，于是在设计上，我们不再需要多加入一个 `init` 状态，而只是需要上述三个状态即可。

而且，既然都是唤醒处于等待状态的协程，我们也不需要再累赘地加入一个 `start` 方法来启动协程，而是统一使用 `resume`，统一了整个唤醒路径，做到了「至简」。

且在实际使用中，你几乎永远不会看到一个所谓处于 `init` 状态的协程，正如操作系统创建了进程、线程之后，进程、线程马上就开始执行了一样，一般情况下你几乎永远不会关心那个「创建了但是还没有完全开始运行」的中间态。

但如果你一定想要区分两者，也很简单，我们还有很多复合方法来检查。

### 复合检查

#### 可用性检查

```php
$coroutine->isAvailable(): bool
```
这个 API 用于检测协程是否可用。

怎么理解「可用」？协程被创建且构造函数执行成功后，直到协程退出之前，都是可用状态。

即，这个协程是可以被操作的，就是可用 (available) 的；反之，如果协程不可用（没有构造完成、退出了），我们也不应该去操作它。

#### 存活检查

```php
$coroutine->isAlive(): bool
```
这个 API 用于检测协程是否存活。

怎么理解「存活」？协程在首次运行之后，直到它退出运行之前，就是存活状态。

即一个协程，开始干活到它干完所有活之前，都是活着的；反之，还没开始干活，或者把它这辈子所有活都干完了，就是没活 or 死的。

基于这个 API，我们就可以知道协程处于等待 (waiting) 状态 ，到底是刚创建完等着开始干活，还是活干到一半在等别的消息 (waiting for I/O)。

> 复合检查其实还是基于三个基本状态 + 协程开始运行时间 + 协程结束时间来做复合检查，协程开始和结束时间被用于跟踪统计，因此整套设计没有引入新的实体，同时保持了三个状态和统一 `resume` 方法唤醒路径的最简设计。

#### 打破砂锅

什么情况下协程会出现创建出来但是没有构造完成？这其实是面向对象设计下特有的场景，考虑以下代码：

```php
$badCoroutine = new class () extends Coroutine {
    public function __construct() {
        // skip parent constructor
        // parent::__construct($callable);
    }
};
```

我们可以通过继承和重写构造函数，来跳过协程的构造，制造出一个不可用的协程。但底层对于此种情况有周全的防护，因此开发者也不必担心写错而导致程序崩溃。

## 协程的观测 (运行状态)

这时候我们也可以意识到一个问题，由于单线程下同时只会有一个协程在运行，因此无论何时我们通过一个协程去观测其它的协程，其它的协程都不会处于「正在运行」的状态。因此，我们只能在当前协程下观测自己，才能得到一个「正在运行」的协程的信息。

我们可以使用 `Coroutine::run()` 来快速运行一个协程，并使用 `Coroutine::getCurrent()` 获取当前协程对象并打印：

```php
use Swow\Coroutine;

function greeting(): void
{
    echo "Hello Swow\n";
    var_dump(Coroutine::getCurrent());
}

Coroutine::run(static function (): void {
    greeting();
});
```

得到以下输出：

```
Hello Swow
object(Swow\Coroutine)#3 (5) {
  ["id"]=>
  int(2)
  ["state"]=>
  string(7) "running"
  ["switches"]=>
  int(0)
  ["elapsed"]=>
  string(3) "0ms"
  ["trace"]=>
  string(%d) "
#0 /path/to/swow/swow-coroutine.php(%d): var_dump(Object(Swow\Coroutine))
#1 /path/to/swow/swow-coroutine.php(%d): Swow\Blog\Coroutine\greeting()
#2 [internal function]: Swow\Blog\Coroutine\{closure}()
#3 /path/to/swow/swow-coroutine.php(%d): Swow\Coroutine::run(Object(Closure))
#4 {main}
"
}
```

可以看到 `state` 是 `running`，即在观测的时候协程正处于运行状态，同时我们也能看到它的调用栈信息。

---

## 协程的观测 (挂起状态) (上)

99%的情况下，我们观测对象都是挂起的协程，这也是 Debugger 的工作原理。

如，我们设计这样的一个 worker 协程来模拟常见的工况：

```php
function work(): void
{
    $count = 0;
    while (true) {
        echo "Do something...\n";
        sleep(1);
        echo sprintf("Task #%s done\n", ++$count);
    }
}
```

把它丢到一个协程中去跑：

```php
use Swow\Coroutine;

Coroutine::run(static function (): void {
    work();
});
```

**但这里出现了一个小插曲，我们会发现这个协程刚开始跑就退出了，这是为什么呢？**

这是因为 Swow 采用了和 Golang 类似的协程模型，即主协程退出以后，所有协程也会一同退出。

因此我们需要在主协程的末尾进行等待操作，这里有很多种方式。

## 协程间的同步机制

### 方案1：sleep()

```php
use Swow\Coroutine;

Coroutine::run(static function (): void {
    work();
});

sleep(5); // 等你五秒，五秒还没搞完我就鲨了你
```

于是我们得到了以下输出：

```
Do something...
Task #1 done
Do something...
Task #2 done
Do something...
Task #3 done
Do something...
Task #4 done
Do something...

Process finished with exit code 0
```

可以看到 Worker 跑到第五次的时候，正好达到了五秒时限，主协程退出，继而进程退出。

这种方式适用于想要控制脚本的最大运行时间的需求（类似于 `max_execution_time`）。

而多数情况下，我们的常驻服务都是一直在跑的状态，而且很多时候我们不好预期程序会跑多久时间，那么这种方式就不是很适用了。

### 方案2：Sync

Sync 模块就是用于多协程同步的，Sync 模块提供了多种同步设施。

#### WaitGroup

首先是 WaitGroup，顾名思义，它被设计用来等待一组操作完成。

WaitGroup 有三个主要的方法：`add`、`done`、`wait`，WaitGroup 内部维护了一个计数器，当调用 `add` 方法的时候，计数加1，表示一个新的任务加入等待；当任务结束时，应该调用 `done` 方法来通知 WaitGroup，此时内部计数会减一；最后，在一个你想要等待所有任务完成的地方使用 `wait` 方法进行等待即可；当计数重新归零的时候，等待的协程会从 `wait` 中被唤醒。

```php
use Swow\Coroutine;
use Swow\Sync\WaitGroup;

function work3(int $id): void
{
    for ($n = 0; $n++ < 3;) {
        echo "Do something...\n";
        sleep(1);
        echo sprintf("Worker[%d] Task#%d done\n", $id, $n);
    }
    echo sprintf("Worker[%d] exit\n", $id);
}

$wg = new WaitGroup();
// $wg->add(3); // 这么写也可以
for ($c = 0; $c < 3; $c++) {
    $wg->add();
    Coroutine::run(static function () use ($c, $wg): void {
        work3($c);
        $wg->done();
    });
};
echo "Wait...\n";
$wg->wait();
echo "Done\n";
```

#### WaitReference

这是 PHP 特有的一种等待机制，只有基于引用计数进行对象生命周期管理的语言才可以实现这样的设施，详见「[引用计数基本知识- Manual - PHP](https://www.php.net/manual/zh/features.gc.refcounting-basics.php)」。

其实和 WaitGroup 一样，WaitReference 内部也维护了一个计数器，但这个计数器其实就是对象本身被引用的次数 (gc.refcount)。

当 WaitReference 对象被闭包函数引用时，WaitReference 引用计数加1，当闭包函数退出时，对象引用计数减1，最终也是调用 `wait` 方法进行等待，当引用计数为0时，也就是 WaitReference 对象被销毁时，等待的协程会被唤醒。

```php
use Swow\Coroutine;
use Swow\Sync\WaitReference;

$wr = new WaitReference();
for ($c = 0; $c < 3; $c++) {
    Coroutine::run(static function () use ($c, $wr): void {
        work3($c);
    });
};
echo "Wait...\n";
$wr::wait($wr);
echo "Done\n";
```

#### waitAll()

这个方法不是很推荐，它是用来等待所有协程退出的，粒度比较粗，写小脚本的时候可以用。

```php
use function Swow\Sync\waitAll;

for ($c = 0; $c < 3; $c++) {
    Coroutine::run(static function () use ($c): void {
        work3($c);
    });
};
echo "Wait...\n";
waitAll();
echo "Done\n";
```

### 方案3：Channel

Channel 永远是协程间通信的首选设施，利用它我们可以实现各种等待机制的组合。

如，我们想要实现这样的效果：当 收到 CTRL +C / 退出信号 或 程序运行超过 5s 或 所有协程运行完毕 后退出。

```php
use Swow\Channel;
use Swow\Signal;

$channel = new Channel();
Coroutine::run(static function () use ($channel): void {
    Signal::wait(Signal::INT);
    $channel->push("Terminated by SIGINT\n");
});
Coroutine::run(static function () use ($channel): void {
    Signal::wait(Signal::TERM);
    $channel->push("Terminated by SIGTERM\n");
});
Coroutine::run(static function () use ($channel): void {
    $wr = new WaitReference();
    for ($c = 0; $c < 3; $c++) {
        Coroutine::run(static function () use ($c, $wr): void {
            work3($c);
        });
    };
    $wr::wait($wr);
    $channel->push("All workers done\n");
});
echo "Wait...\n";
$timeout = 5;
try {
    echo $channel->pop($timeout * 1000);
} catch (ChannelException $exception) {
    echo sprintf("Timeout after %d seconds\n", $timeout);
}
```

### 输出结果

#### 正常结束

```
Do something...
Do something...
Do something...
Wait...
Worker[0] Task#1 done
Do something...
Worker[1] Task#1 done
Do something...
Worker[2] Task#1 done
Do something...
Worker[0] Task#2 done
Do something...
Worker[1] Task#2 done
Do something...
Worker[2] Task#2 done
Do something...
Worker[0] Task#3 done
Worker[0] exit
Worker[1] Task#3 done
Worker[1] exit
Worker[2] Task#3 done
Worker[2] exit
All workers done
```

#### 超时 ($timeout = 1)

```
Do something...
Do something...
Do something...
Wait...
Worker[0] Task#1 done
Do something...
Worker[1] Task#1 done
Do something...
Worker[2] Task#1 done
Do something...
Timeout after 1 seconds
```

#### 被 CTRL + C 中断

```
Do something...
Do something...
Do something...
Wait...
Worker[0] Task#1 done
Do something...
Worker[1] Task#1 done
Do something...
Worker[2] Task#1 done
Do something...
Terminated by SIGINT
```

## 协程的观测 (挂起状态) (下)

搞懂了协程间的同步机制以后，我们就可以继续研究怎么观测挂起的协程了。

我们可以启动一个专门用来观测 Worker 的协程，不停地打印 Worker 的状态：

```php
use Swow\Coroutine;

$worker = Coroutine::run(static function (): void {
    work();
});

// This is a watcher
Coroutine::run(static function () use ($worker): void {
    while (true) {
        var_dump($worker);
        sleep(1);
    }
});

sleep(5);
```

但这并不是很实用，因为实际情况下，我们并不总是能拿到协程对象，因此我们想要实现一个通用的观测器，可以用下面的写法：

```php
use Swow\Coroutine;

Coroutine::run(static function (): void {
    work();
});

// This is a better watcher
Coroutine::run(static function (): void {
    while (true) {
        var_dump(Coroutine::getAll());
        sleep(1);
    }
});

sleep(5);
```

通过 `Coroutine::getAll()` 方法我们可以拿到所有已经开始运行且尚未死亡的协程，通过打印这个列表我们就能快速地查看所有协程的信息，这可以快速地帮助我们在实际开发中定位 BUG。

当然，这只是一个「生活小妙招」，使用 Swow 提供的 SDB (Swow Debugger) 组件可以更加方便地帮助开发者定位具体问题，且可以对协程进行动态调试，关于 SDB 的具体内容会在后续的文章中详细介绍。

## 协程的让出与唤醒

这是协程最基本的两个功能，但就是这两个功能，支撑了整个协程系统的调度，但作为开发者，我们一般不会在 Swow 中直接用到这两个功能，但我们仍然可以浅浅地了解一下它：

```php
$coroutine = new Coroutine(static function (int $a, int $b): int {
    $aPlusB = $a + $b;
    $multiplier = Coroutine::yield($aPlusB);
    return $aPlusB * $multiplier; // `return $value` is equal to `Coroutine::yield($value)` + `exit()`
});
$aPlusB = $coroutine->resume(1, 1);
var_dump($aPlusB);
$result = $coroutine->resume(2);
var_dump($result);
```

```
int(2)
int(4)
```

可以看到，协程在调度的同时还可以传递变量，基于这个特性，我们甚至可以用 PHP 来实现一个 Channel。

当然，多数时候，我们应当使用 Channel 来替代直接使用调度 API，对我们的协程进行调度，因为官方的 Channel 在设计上会更加精妙，覆盖更多场景以及拥有更好的调度性能，且能够同时调度多个协程，开发者也可以更好地遵循 CSP 模型来编写代码，降低心智负担。

## 协程的返回值

从上面的例子可以看到，我们将 `return $value` 等价视为 `yield` 一个变量并退出协程的组合操作，因此我们无需再引入一个 `$coroutine->getReturn()` 方法，且再次统一了变量传递的路径，做到了「至简」的设计。

并且，绝大多数情况下我们并不会用到函数的返回值，因为在 CSP 模型的哲学下，协程间信息的传递一定要通过 Channel，因为为协程的返回值设计一个 API 是违反设计哲学的做法，会将开发者引导向错误的道路。

就好像在多线程的模型中，你可以提供一个函数给线程 API 运行，但线程函数的返回值表示的是退出状态，而非数据，因此线程模型下，返回值亦不涉及数据的传递，数据的传递需要通过别的途径进行。

但为什么要遵循 CSP，数据传递都通过 Channel 进行有什么好处，这些内容我应该会单独开一篇文章来进行讲解，篇幅原因，不在此展开。

当然，由于 Swow 提供了全套的基本能力以及极佳的扩展性，我们仍然可以在应用层实现 `getReturn`，乃至扩展更多的，我们想要的任何功能：

```php
use Swow\Coroutine;

$coroutine = new class (static function (): string {
    return "Hello Swow\n";
}) extends Coroutine {
    public mixed $returnValue = null;

    public function __construct(callable $callable)
    {
        parent::__construct(function () use ($callable): void {
            $returnValue = $callable();
            $this->returnValue = $returnValue;
        });
    }

    public function getReturn(): mixed
    {
        return $this->returnValue;
    }
};
$coroutine->resume();
var_dump($coroutine->getReturn());
```

```php
Hello Swow
```

## 协程的无处不在

全文我们讲了很多协程的内容，但 Swow 在协程上有一个微小但好用的突破是：我们终于拥有了真正意义上的主协程。

主协程的好处是什么，是你不再需要创建协程就可以跑协程的代码吗？当然不仅仅是这个，它的好处远不止于此... 但关于主协程的好处有哪些，或许又需要有一篇新的文章来支撑关于它的大量内容。

总之，你现在需要知道的是，在 Swow 中，协程无处不在，你可以尽情地开始书写属于你的第一个 Swow 脚本。

