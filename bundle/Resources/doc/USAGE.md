# Usage

> All the configuration is SiteAccessAware then you can have different one depending on the SiteAccess

## Basic Configuration

```yaml
# app/config/config.yml

ez_platform_automated_translation:
    system:
        default:
            configurations:
                google:
                    apiKey: "google-api-key"
                deepl:
                    authKey: "deepl-pro-key"
```

