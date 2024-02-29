<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_quiz\router\schema\objects;

use core\router\schema\objects\type_base;
use core\router\schema\specification;

/**
 * A JSON list, or a name-keyed PHP array, with an explicit item schema.
 *
 * @package   mod_quiz
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class array_of_items extends type_base {
    /**
     * Constructor.
     *
     * @param type_base $items Schema for each item.
     * @param bool $keyed Whether items are keyed by name rather than a sequential list.
     */
    public function __construct(
        /** @var type_base Schema for each item. */
        protected type_base $items,
        /** @var bool Whether items are keyed by name rather than a sequential list. */
        protected bool $keyed = false,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function get_openapi_description(specification $api, ?string $path = null): \stdClass {
        $items = $this->items->get_openapi_schema($api);
        if ($this->keyed) {
            // PHP encodes an empty map as [], but populated name-keyed arrays as objects.
            return (object) [
                'anyOf' => [
                    (object) ['type' => 'object', 'additionalProperties' => $items],
                    (object) ['type' => 'array', 'maxItems' => 0, 'items' => false],
                ],
            ];
        }
        return (object) ['type' => 'array', 'items' => $items];
    }

    #[\Override]
    public function validate_data(mixed $data) {
        if (!is_array($data)) {
            throw new \invalid_parameter_exception('Invalid data type, expected an array.');
        }
        if (!$this->keyed && !array_is_list($data)) {
            throw new \invalid_parameter_exception('Invalid data type, expected a list.');
        }
        if ($this->keyed && $data !== [] && array_is_list($data)) {
            throw new \invalid_parameter_exception('Invalid data type, expected a name-keyed array.');
        }
        foreach ($data as $item) {
            $this->items->validate_data($item);
        }
        return $data;
    }
}
