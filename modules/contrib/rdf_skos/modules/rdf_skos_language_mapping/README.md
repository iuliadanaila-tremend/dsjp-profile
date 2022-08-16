# RDF SKOS Language mapping

Provides a mapping between Drupal language codes and the ones present in Skos concepts and concept schemes.

The need for this module arises due to potential mismatch between the language codes used in the Skos data and the ones Drupal expects. 

For example, Drupal uses `pt-pt` for Portuguese (from Portugal) but the Skos data may be using simply `pt` having already assumed the country specificity. With this module you can map the two language codes so that the Skos entities can be loaded correctly in all languages.

# Permissions

The module ships with a new permission that allows access to configure the language mapping:

```
administer rdf skos language mapping
```