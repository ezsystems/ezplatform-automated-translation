# Features

## Translation command

A command can Translate a Content for you

`php bin/console eztranslate [contentId] [serviceName] --from=eng-GB --to=fre-FR`


## Adding your own Remote Translation Service

This bundle enables you to provide your own Translation mechanism.

To do so, you need to:

- create a service that implements EzSystems\EzPlatformAutomatedTranslation\Client\ClientInterface
- implements the method
- tag this service: `ezplatform.automated_translation.client`  
