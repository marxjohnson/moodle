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

use core\param;
use core\router\schema\objects\scalar_type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for collection schema validation.
 *
 * @package   mod_quiz
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(className: array_of_items::class)]
final class array_of_items_test extends \advanced_testcase {
    /**
     * Collection validation accepts empty arrays and validates individual items.
     */
    public function test_array_of_items_validation(): void {
        $list = new array_of_items(new scalar_type(param::INT));
        $this->assertSame([], $list->validate_data([]));
        $this->assertSame([1, 2], $list->validate_data([1, 2]));
        $map = new array_of_items(new scalar_type(param::INT), keyed: true);
        $this->assertSame([], $map->validate_data([]));
        $this->assertSame(['area' => 1], $map->validate_data(['area' => 1]));
    }

    /**
     * Invalid collection shapes and items fail explicitly.
     *
     * @param mixed $data
     * @param bool $keyed
     */
    #[DataProvider('invalid_collection_provider')]
    public function test_array_of_items_invalid(mixed $data, bool $keyed): void {
        $schema = new array_of_items(new scalar_type(param::INT), keyed: $keyed);
        $this->expectException(\invalid_parameter_exception::class);
        $schema->validate_data($data);
    }

    /**
     * Data provider with various examples of invalid item arrays.
     *
     * @return array
     */
    public static function invalid_collection_provider(): array {
        return [
            'not an array' => [null, false],
            'map instead of list' => [['area' => 1], false],
            'sparse list' => [[1 => 1], false],
            'list instead of map' => [[1], true],
            'invalid list item' => [['invalid'], false],
            'invalid map item' => [['area' => 'invalid'], true],
        ];
    }
}
