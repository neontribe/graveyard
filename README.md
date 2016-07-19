# nt2_entity

## Final goal

We want to get a place where, given a valid property reference and API details, a TABS Property can be read into a Drupal entity, then rendered.

## How to get there

  * Use Neontabs IO module to fetch the JSON
  * Create a module that defines a stub, non array values, for the property entity
  * Create a vocabulary and terms for attributes.
    * Initial just storing the label as a term title.
    * Next a term entity with firlds for each of the TABS fields on an attribute.
  * Associate the attributes of a property with terms in the vocabulary
  * Add images to the entity (use the external image cache and image optimise modules to help with this)
  * Create a multi hiearchy vocabulary for areas and locations.

We'll re-assess here, we have co-ordinates, brands, address to consider
