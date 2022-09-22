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
    public static function toTree($data = [], $primaryId = 'id', $parentId = 'parent_id', $children = 'children')
    {
        // data is empty
        if (count($data) === 0) {
            return null;
        }

        // parameter missing
        if (!array_key_exists($primaryId, head($data)) || !array_key_exists($parentId, head($data))){
            return null;
        }

        // map
        $items = array();
        foreach ($data as $v) {
            $v[$children] = [];
            $items[@$v[$primaryId]] = $v;
        }

        // tree
        $tree = array();
        foreach ($items as &$item) {
            if (array_key_exists($item[$parentId], $items)) {
                $items[$item[$parentId]][$children][] = &$items[$item[$primaryId]];
            } else {
                $tree[] = &$items[$item[$primaryId]];
            }
        }

        return $tree;
    }
}
