<?php

declare(strict_types=1);

namespace E1on\OminesDatatablesElasticsearchAdapterBundle;

use E1on\OminesDatatablesElasticsearchAdapterBundle\Enum\DataTableSessionEnum;
use E1on\OminesDatatablesElasticsearchAdapterBundle\Provider\SessionProvider;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\MultiMatch;
use Elastica\Result;
use Elastica\Search;
use Omines\DataTablesBundle\Adapter\AbstractAdapter;
use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Traversable;

class ElasticaAdapter extends AbstractAdapter
{
    private const DEFAULT_ORDER_FIELD    = 'id';
    private const DEFAULT_SORT_DIRECTION = 'ASC';

    private array $clientSettings = [];

    private array $indices = [];

    private SessionInterface $session;

    public function __construct(
        private readonly SessionProvider $sessionProvider,
    ) {
        $this->session = $this->sessionProvider->provide();

        parent::__construct();
    }

    public function configure(array $options): void
    {
        $this->clientSettings = $options['client'];
        $this->indices        = (array) $options['index'];
    }

    protected function prepareQuery(AdapterQuery $query): void
    {
        $query->set('client', new Client($this->clientSettings));

        foreach ($query->getState()->getDataTable()->getColumns() as $column) {
            if ($column->getField() === null) {
                $column->setOption('field', $column->getName());
            }
        }

        if (!$query->getState()->getStart()) {
            $this->clearMetaData(
                $query->getState()->getDataTable()->getName()
            );
        }
    }

    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column): string
    {
        return sprintf('[%s]', $column->getField());
    }

    protected function getResults(AdapterQuery $query): Traversable
    {
        $state  = $query->getState();
        $search = new Search($query->get('client'));
        $search->addIndices($this->indices);

        $queryBuilder = $this->buildQuery($state);

        if ($state->getLength() !== null) {
            $queryBuilder->setFrom($state->getStart())->setSize($state->getLength());
        }

        $this->applyOrdering($queryBuilder, $state);

        $resultSet = $search->search($queryBuilder);

        $query->setTotalRows($resultSet->getTotalHits());

        /** @phpstan-ignore-next-line */
        $query->setFilteredRows($search->count());

        $results = $resultSet->getResults();

        $this->saveMetaData($state, $results);

        foreach ($results as $result) {
            yield $result->getData();
        }
    }

    protected function buildQuery(DataTableState $state): Query
    {
        $q = new Query();

        if (!empty($globalSearch = $state->getGlobalSearch())) {
            $fields = [];

            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable()) {
                    $fields[] = $column->getField();
                }
            }

            $multiMatch = (new MultiMatch())->setQuery($globalSearch)->setFields($fields);
            $q->setQuery($multiMatch);
        }

        return $q;
    }

    protected function applyOrdering(Query $query, DataTableState $state): void
    {
        $sortDirection    = self::DEFAULT_SORT_DIRECTION;
        $dataTable        = $state->getDataTable()->getName();

        $currentDocument  = $this->session->get(DataTableSessionEnum::getCurrentDocument($dataTable));
        $previousDocument = $this->session->get(DataTableSessionEnum::getPreviousDocument($dataTable));
        $from             = $this->session->get(DataTableSessionEnum::getForm($dataTable));

        foreach ($state->getOrderBy() as [$column, $direction]) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable() && $orderField = $column->getOrderField()) {
                $query->addSort([$orderField => ['order' => $direction]]);

                $sortDirection = $direction;

                if ($state->getDraw() > 2) {
                    $document = $query->getParam('from') > $from ? $currentDocument : $previousDocument;

                    if ($document) {
                        $query->addParam('search_after', $this->getValueFromArray($document, $orderField));
                    }
                }
            }
        }

        $query->addSort([self::DEFAULT_ORDER_FIELD => ['order' => $sortDirection]]);

        if ($state->getDraw() > 2) {
            if ($query->getParam('from')) {
                $document = $query->getParam('from') > $from ? $currentDocument : $previousDocument;

                if ($document) {
                    $query->addParam('search_after', $document[self::DEFAULT_ORDER_FIELD]);

                    $query->setFrom(0);
                }
            }
        }
    }

    private function saveMetaData(DataTableState $state, array $results): void
    {
        /** @var Result|null $lastDocument */
        $lastDocument = end($results);
        $dataTable    = $state->getDataTable()->getName();

        if (!$lastDocument) {
            return;
        }

        $currentDocument = $this->session->get(DataTableSessionEnum::getCurrentDocument($dataTable));
        $betweenDocument = $this->session->get(DataTableSessionEnum::getBetweenDocument($dataTable));

        $this->session->set(DataTableSessionEnum::getCurrentDocument($dataTable), $lastDocument->getData());

        if ($currentDocument) {
            $this->session->set(DataTableSessionEnum::getBetweenDocument($dataTable), $currentDocument);
        }

        if ($betweenDocument) {
            $this->session->set(DataTableSessionEnum::getPreviousDocument($dataTable), $betweenDocument);
        }

        $this->session->set(DataTableSessionEnum::getForm($dataTable), $state->getStart());
    }

    private function clearMetaData(string $dataTable): void
    {
        $this->session->remove(DataTableSessionEnum::getCurrentDocument($dataTable));
        $this->session->remove(DataTableSessionEnum::getBetweenDocument($dataTable));
        $this->session->remove(DataTableSessionEnum::getPreviousDocument($dataTable));
        $this->session->remove(DataTableSessionEnum::getForm($dataTable));
    }

    private function getValueFromArray(array $array, string $keyString): string
    {
        $keys = explode('.', $keyString);

        $value = $array;

        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return '';
            }
        }

        return $value;
    }
}
