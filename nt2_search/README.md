## NT2 Search

The `nt2_search` module handles:

* Interfacing with Tabs API for property searches
* Generating search form blocks based on the search term filters Tabs API provides
* Rendering search results

### Goals

* **Extensability** - all per-site customisation to search forms and results behaviour should be possible from external modules with hooks: no per-site code should be in this module. *(see the **Extensability** section for more information)*
* **Hardcoding** - hardcoding should be limited where possible. Generic `Term` classes should be used, with the only hardcoding being in the `getTerms()` function, where `Term` classes are instantiated with custom parameters from the `core` attributes.

### State

* [x] Performing searches with Tabs API
* [x] Rendering basic search results
* [x] Having a search results page that can be bookmarked
* [x] Making simple attempts to provide most of the Tabs API search terms in search forms
* [x] Providing configuration menus for controlling the behaviour of search forms
* [ ] Efficient usage of Tabs API, with caching and smart error handling
* [ ] Providing *all* core search terms implemented in the current sites in search forms
* [ ] Providing an extensible API for customising behaviour for a particular site
* [ ] [Anything else here](https://github.com/neontabs/nt2/issues)

### So you want to...

#### Perform a  Search with Tabs API

The `SearchTabs` class may be used for directly interfacing with Tabs API. One must generate docs to explore the full potential of the methods provided by the class but a simple search can be performed as follows:

```php
$query = array(
  'pets' => TRUE,
);

$propertyNodes = SearchTabs::findProperties($query);
```

The basic method returns property nodes from `nt2_node_type` but other methods are provided for just returning the raw property references.

#### Add to, remove from or change the way search forms are rendered

##### For all sites

`nt2_search` uses an abstraction to handle the generation of forms from Tabs API. The `searchTerms` section of Tabs API is parsed and from this, `Term` instances are created, which handle the creation of form elements that the user can manipulate in a search form to produce part of a search query.

The code that handles this can be found in the `SearchTabs` class, in particular the `getTerms()` method. In this method, the Tabs API is queried and relevent `Term` objects are instantiated.

Different implementations of the abstract `Term` class are available for supporting different types of filters with different form elements. For example, the `CheckboxTerm` may be used to provide a boolean value for the query based on a checkbox form element in a search form.

If one wishes to add support for different types of rendering or to implement search terms provided by Tabs but not yet supported in `nt2_search`'s search forms (*e.g.* a map for area selection), then one is best off adding the code to achieve this here.

It is of note that one can also add an alternative implementation for the same Tabs search terms. For example, as of present, boolean attribute search terms from Tabs API are implemented with `CheckboxTerm` but - if one wished to provide an alternative view in a search form - an alternative `Term` implementation could be instantiated and added to the list of search terms. `nt2_search` is smart enough to work out that these two `Term` objects provide for the same code and will hence not let them be enabled at the same time. For this reason, one can safely add their own `Term` implementations without fear of conflicts: it is part of `nt2_search`'s purpose to not let this happen.

Owing to all of this, implementing a new search term is as simple as this:

1. Implement the `Term` abstract class or extend an existing one - the four abstract methods are usually all that need to be implemented.
1. Instantiate the `Term` instance in `getTerms()`, after checking the existence of the relevent search terms in the Tabs API response.
1. Login to the Drupal site and enable the new `Term` in the `nt2_search` configuration page.

**TODO** - example code here would be lovely

Usually the process is as simple as that. See `CheckboxTerm` for a good example implementation of a `Term`. Some more complex features such as dependencies on other `Term`s' visibility are supported out of the box but shouldn't need to be touched for the most part.

##### For an individual site

Modifying the search form should be as simple as implementing a custom `Term` - as described above - in a separate module, then injecting this `Term` with the provided hook for doing so. After this has happened, the new `Term` will show up in the module's configuration panel and can be made visible to add to a form or to replace another, more undesirable `Term`, lacking the custom additions.

At this point in time, this functionality is not implemented and is waiting on the implementation of the promised hooks in the [Extensability](#extensability) section of this README. Expect example code to be added once this functionality has been provided.

### Extensability

This module is designed to be extensable. Almost all common changes to functionality should be achievable by creating a custom `Term` instance and/or using hooks.

All per-site customisation should be possible without modification of this module; one should be able to just use the hooks and classes that it exposes. If this is not the case, it is a failing of the module and the desired extensability should be added to this module with a hook, **then** implemented with a module personal to the site.

At this stage in time, hooks are not implemented. However, one can at least expect the following to be exposed with hooks:

* A hook to allow custom `Term` implementations to be added to the return value of `getTerms()`
* A hook to allow the customisation of the ordering and visibility of search results
