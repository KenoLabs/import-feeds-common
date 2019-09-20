<?php

declare(strict_types=1);

namespace Import\Types\Simple\Handlers;

use Espo\Core\Exceptions\Error;

/**
 * Class DefaultHandler
 *
 * @author r.zablodskiy@treolabs.com
 */
class DefaultHandler extends AbstractHandler
{
    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function run(array $fileData, array $data): bool
    {
        // prepare entity type
        $entityType = (string)$data['data']['entity'];

        // prepare import result id
        $importResultId = (string)$data['data']['importResultId'];

        // create service
        $service = $this->getServiceFactory()->create($entityType);

        // prepare id field
        $idField = isset($data['data']['idField']) ? $data['data']['idField'] : null;

        // find ID row
        $idRow = $this->getIdRow($data['data']['configuration'], $idField);

        // find exists if it needs
        $exists = [];
        if (in_array($data['action'], ['update', 'create_update']) && !empty($idRow)) {
            $exists = $this->getExists($entityType, $idRow['name'], array_column($fileData, $idRow['column']));
        }

        // prepare file row
        $fileRow = (int)$data['offset'];

        // save
        foreach ($fileData as $row) {
            // increment file row number
            $fileRow++;

            // prepare id
            if ($data['action'] == 'create') {
                $id = null;
            }
            if ($data['action'] == 'update') {
                if (isset($exists[$row[$idRow['column']]])) {
                    $id = $exists[$row[$idRow['column']]];
                } else {
                    // skip row if such item does not exist
                    continue 1;
                }
            }
            if ($data['action'] == 'create_update') {
                $id = (isset($exists[$row[$idRow['column']]])) ? $exists[$row[$idRow['column']]] : null;
            }

            // prepare entity
            $entity = null;

            try {
                // prepare row
                $input = $this->prepareRow($row, $data);

                // begin transaction
                $this->getEntityManager()->getPDO()->beginTransaction();

                if (empty($id)) {
                    $entity = $service->createEntity($input);
                } else {
                    $entity = $service->updateEntity($id, $input);
                }

                $this->getEntityManager()->getPDO()->commit();
            } catch (\Throwable $e) {
                // roll back transaction
                $this->getEntityManager()->getPDO()->rollBack();

                // push log
                $this->log($entityType, $importResultId, 'error', (string)$fileRow, $e->getMessage());
            }

            if (!is_null($entity)) {
                // prepare action
                $action = empty($id) ? 'create' : 'update';

                // push log
                $this->log($entityType, $importResultId, $action, (string)$fileRow, $entity->get('id'));
            }
        }

        return true;
    }

    /**
     * Prepare row for saving
     *
     * @param array $row
     * @param array $data
     *
     * @return \stdClass
     * @throws Error
     */
    protected function prepareRow(array $row, array $data): \stdClass
    {
        // create row
        $inputRow = new \stdClass();

        // prepare row
        foreach ($data['data']['configuration'] as $item) {
            $this->convertItem($inputRow, (string)$data['data']['entity'], $item, $row, $data['data']['delimiter']);
        }

        return $inputRow;
    }
}