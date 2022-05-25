<?php

namespace ZhenMu\Support\Utils;

class Tree
{
    /**
     * @param array $data
     * @param string $primary
     * @param string $parent
     * @param string $children
     * @return null|array
     * #
     */
    public static function toTree($data = [], $primary = 'id', $parent = 'parent_id', $children = 'children')
    {
        // data is empty
        if (count($data) === 0) {
            return null;
        }

        // parameter missing
        if (!array_key_exists($primary, head($data)) || !array_key_exists($parent, head($data))){
            return null;
        }

        $items = array();
        foreach ($data as $v) {
            $items[@$v[$primary]] = $v;
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
