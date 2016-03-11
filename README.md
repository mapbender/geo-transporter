# Geo-Transporeter

Transport geo data between databases

# Symfony 

## Service

```php
$geoTransporter = $this->getContainer()->get('geo_transporter');
```


## CLI Using 

```bash 
cd application
app/console geo:transport <location> <object-type>
```