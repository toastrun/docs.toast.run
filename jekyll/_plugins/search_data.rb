# frozen_string_literal: true

# One search index per scope, generated from _includes/search-data.html.
#
# A hand-written page loads the "main" index: the hand-written pages plus the
# searchable API versions. An API page loads the index of its own version.
module SearchData
  # Pages are generated after the site is read, so the page list is complete.
  class IndexPage < Jekyll::PageWithoutAFile
    def initialize(site, name, liquid)
      super(site, site.source, 'assets/js', "search-data-#{name}.json")
      self.data['layout'] = nil
      self.data['sitemap'] = false
      self.content = liquid
    end
  end
end

Jekyll::Hooks.register :site, :post_read do |site|
  versions = site.data['versions'] || []
  searchable = versions.select { |v| v['search'] }.map { |v| v['ref'] }

  # main: the hand-written pages and every searchable version
  main = "{% include search-data.html mode='main' versions='#{searchable.join(',')}' %}"
  site.pages << SearchData::IndexPage.new(site, 'main', main)

  # one index per API version, all of them, so every API page can search itself
  versions.each do |version|
    ref = version['ref']
    content = "{% include search-data.html mode='version' version='#{ref}' %}"
    site.pages << SearchData::IndexPage.new(site, ref, content)
  end
end
