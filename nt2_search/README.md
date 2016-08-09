## Searching

### Requirements

 * Must be accessible from a book marked URL
 * Must be accessible from the search form.
 * It should use the searchId parameter to save the sort order of the properties returned.
 * it should paginate in pages of a configurable size.
 
### Process

 1. The search implementaion should query the API withthe specified parameters/filter but request just the property references in the response.  Use pageSize=99999 to load all properties.
 1. This list of property references should be passed to the theming function(s) that will show the first n properties.
   * Ideally this will update the URL to hold the parameters, search id, offset and page size.
 1. Pagination should support, first/last page, previous/next, and numbered pages.
   * Numbered pages shoud only show +/- n pages fromn the current e.g. .. 3 4 5 6 7 if the current page is 5 and the +/- value is set to 2
   * Current page shuold not be linked.
   
### Problems/Notes

 * The inital search will return a long list of property references, we need to persist this somehow.  Cookies seem to be the best place to store these http://php.net/manual/en/function.setcookie.php
 * When we first hit the search page we won't have a search ID in the URL

Please feel free to add any other notes.
