neonmap
=======


If you're looking to use this to draw a map, with clustering, from geoJSON data, everything you need is in the /dist directory. You'll find the examples in /demo helpful. There's an example of what geoJson should look like at ./demo/fixture.json.

usage
=====

Include dist/neonmap.js and dist/neonmap.css in your page. 

If you're supporting IE8 and your environment isn't properly shimmed you can use the shims provided like this:

```
    <!--[if lt IE 9]>
        <script src="dist/shims.min.js"></script>
    <![endif]-->
```

neonmap.js provides a global variable ```neonmap``` which is a function you can use to construct a new map. It takes two arguments, an element (normally a div) and an options object. The options you supply will be deep extended over the defaults in index.js. You may supply pin data in geoJson format in the geoJson option. Both individual features and featureCollections are acceptable.

Use with jQuery
---------------
If neonmap detects a jQuery installation in the environment it will register a jQuery plugin called ```neonmap```, you can invoke it like this:

```
    jQuery('#map').neonmap({...options...});
```

Configuration via data attributes
---------------------------------
Sometimes it is nice to supply configuration declaratively in your HTML rather than programmatically in javascript. Any option can be supplied in this way:

```
    <div id="map" data-attribution="Override the map attribution message"></div>
```

Clustering options
------------------
Options in the ```clustering``` property of the configuration are passed to leaflet.markercluster. You can see what's available here: https://github.com/Leaflet/Leaflet.markercluster#defaults

Popup options
-------------
Most options in the ```popups``` configuration are passed through to Leaflet's bindPopup method http://leafletjs.com/reference.html#popup, the exception to this is the ```template``` string, which should be a [Mustache](https://github.com/janl/mustache.js) template, which will be supplied with the geoJson Feature's properties at render-time.

developing
==========

```
    git clone git@github.com:neontribe/neonmap.git
    cd neonmap
    npm install
```

And you're done. See package.json:scripts for utility commands.
