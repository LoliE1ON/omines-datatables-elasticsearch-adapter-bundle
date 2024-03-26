Elasticsearch adapter with search_after feature for [DataTable Omines](https://github.com/omines/datatables-bundle) Bundle
=

This bundle provides integration between Elasticsearch and the DataTable bundle for Symfony, enabling efficient querying of Elasticsearch indices and fetching results using the search_after feature. This allows for pagination and quick processing of large volumes of data presented in the DataTable format, leveraging the powerful search capabilities of Elasticsearch. 

### Install
```bash
composer require e1on/omines-datatables-elasticsearch-adapter-bundle
```

### Rules
- Setting a name for a DataTable is required 
- Uniq DataTable name for each table

### Using

```php
use E1on\OminesDatatablesElasticsearchAdapterBundle\ElasticaAdapter;

$table = $this->createDataTable()
    ->setName('log')
    ->add('timestamp', DateTimeColumn::class, ['field' => '@timestamp', 'format' => 'Y-m-d H:i:s', 'orderable' => true])
    ->add('level', MapColumn::class, [
        'default' => '<span class="label label-default">Unknown</span>',
        'map' => ['Emergency', 'Alert', 'Critical', 'Error', 'Warning', 'Notice', 'Info', 'Debug'],
    ])
    ->add('message', TextColumn::class, ['globalSearchable' => true])
    ->createAdapter(ElasticaAdapter::class, [
        'client' => ['host' => 'elasticsearch'],
        'index' => 'logstash-*',
    ]);
```