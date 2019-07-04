ACF OpenStreetMap Field
=======================

Configurable OpenStreetMap or Leaflet Field in ACF.  
Requires ACF 5.7+

Features
--------
 - Configurable Map-Tile Provider
 - Selectable Map Overlays
 - Multiple Markers
 - Ready-to-use HTML-Output
 - Custom map markers [through WordPress filters](../../wiki/HTML-Marker-Icon) and JS Events.


 Installation
 ------------

  ### Production (using Github Updater – recommended for Multisite)
   - Install [Andy Fragen's GitHub Updater](https://github.com/afragen/github-updater) first.
   - In WP Admin go to Settings / GitHub Updater / Install Plugin. Enter `mcguffin/acf-openstreetmap-field` as a Plugin-URI.

  ### Development
   - cd into your plugin directory
   - $ `git clone git@github.com:mcguffin/acf-openstreetmap-field.git`
   - $ `cd acf-openstreetmap-field`
   - $ `npm install`
   - $ `npm run dev`


Developing
----------
This plugin uses gulp.

To get started `cd` in the plugin directory,  
run `npm install` then `gulp`

Usage
-----
There is some developer centric documentation in the [wiki](../../wiki).

Thanks
------

This plugin wouldn't have been possible without these awesome people and Projects:

 - Jan Pieter Waagmeester ([Leaflet Providers](https://github.com/leaflet-extras/leaflet-providers))
 - Per Liedman ([Leaflet Control Geocode](https://github.com/perliedman/leaflet-control-geocoder))
 - The entire [Leaflet](https://leafletjs.com/) Project
