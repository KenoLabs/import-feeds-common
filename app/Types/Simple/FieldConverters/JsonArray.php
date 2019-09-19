<?php
/**
 * Import
 * TreoPIM Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of Zinit Solutions GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <http://treopim.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

/**
 * Class JsonArray
 *
 * @author r.zablodskiy@treolabs.com
 */
class JsonArray extends AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        $value = null;

        $value = isset($row[$config['column']]) ? $row[$config['column']] : $row[$config['default']];

        if (is_string($value)) {
            $value = !empty($v = trim($value, "[]")) ? explode($delimiter, $v) : [];
        }

        $inputRow->{$config['name']} = $value;
    }
}
