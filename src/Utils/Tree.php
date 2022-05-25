<?php

namespace App\Utilities;

class CollectionUtility
{
    /**
     * @param array $data
     * @param string $primary
     * @param string $parent
     * @param string $children
     * @return array
     * #
     */
    public static function toTree($data, $primary = 'id', $parent = 'parent_id', $children = 'children')
    {
        if (!isset($data[0][$parent])) {
            return [];
        }

        $items = array();
        foreach ($data as $v) {
            $items[$v[$primary]] = $v;
        }

        $tree = array();
        foreach ($items as $item) {
            if (isset($items[$item[$parent]])) {
                $items[$item[$parent]][$children][] = &$items[$item[$primary]];
            } else {
                $tree[] = &$items[$item[$primary]];
            }
        }

        return $tree;
    }
}
