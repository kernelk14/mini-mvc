<?php
/**
 * Simple paginator for query results.
 * PHP 5.0+ compatible.
 */
class Core_Paginator
{
    var $items = array();
    var $total = 0;
    var $perPage = 15;
    var $currentPage = 1;
    var $lastPage = 1;
    var $pageParam = 'page';
    var $baseUrl = null;

    function __construct($items, $total, $perPage, $currentPage, $pageParam = 'page')
    {
        $this->Core_Paginator($items, $total, $perPage, $currentPage, $pageParam);
    }

    function Core_Paginator($items, $total, $perPage, $currentPage, $pageParam = 'page')
    {
        $this->items = is_array($items) ? $items : array();
        $this->total = (int)$total;
        $this->perPage = (int)$perPage > 0 ? (int)$perPage : 15;
        $this->currentPage = (int)$currentPage > 0 ? (int)$currentPage : 1;
        $this->pageParam = $pageParam ? (string)$pageParam : 'page';
        $this->lastPage = $this->perPage > 0 ? (int)ceil($this->total / $this->perPage) : 1;
        if ($this->lastPage < 1) { $this->lastPage = 1; }
        if ($this->currentPage > $this->lastPage) { $this->currentPage = $this->lastPage; }
    }

    function items() { return $this->items; }
    function total() { return $this->total; }
    function perPage() { return $this->perPage; }
    function currentPage() { return $this->currentPage; }
    function lastPage() { return $this->lastPage; }
    function count() { return count($this->items); }
    function isEmpty() { return count($this->items) === 0; }
    function hasPages() { return $this->lastPage > 1; }
    function hasMore() { return $this->currentPage < $this->lastPage; }
    function onFirstPage() { return $this->currentPage <= 1; }

    function from()
    {
        if ($this->total === 0) { return 0; }
        return (($this->currentPage - 1) * $this->perPage) + 1;
    }

    function to()
    {
        if ($this->total === 0) { return 0; }
        $to = $this->currentPage * $this->perPage;
        return $to > $this->total ? $this->total : $to;
    }

    function setBaseUrl($url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    function url($page)
    {
        $page = (int)$page;
        if ($page < 1) { $page = 1; }
        $base = $this->baseUrl;
        if ($base === null || $base === '') {
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
            $qpos = strpos($uri, '?');
            $base = $qpos !== false ? substr($uri, 0, $qpos) : $uri;
        }
        $params = array();
        if (isset($_GET) && is_array($_GET)) {
            foreach ($_GET as $k => $v) { $params[$k] = $v; }
        }
        if ($page <= 1) {
            unset($params[$this->pageParam]);
        } else {
            $params[$this->pageParam] = $page;
        }
        $qs = '';
        if (function_exists('http_build_query')) {
            $qs = http_build_query($params);
        } else {
            $pairs = array();
            foreach ($params as $k => $v) {
                $pairs[] = urlencode($k) . '=' . urlencode($v);
            }
            $qs = implode('&', $pairs);
        }
        return $base . ($qs !== '' ? '?' . $qs : '');
    }

    function previousPageUrl()
    {
        if ($this->currentPage > 1) { return $this->url($this->currentPage - 1); }
        return null;
    }

    function nextPageUrl()
    {
        if ($this->currentPage < $this->lastPage) { return $this->url($this->currentPage + 1); }
        return null;
    }

    function _esc($value)
    {
        if (class_exists('Core_View') && method_exists('Core_View', 'escape')) {
            return Core_View::escape($value);
        }
        return htmlspecialchars((string)$value, ENT_QUOTES);
    }

    function _pageLink($page, $current)
    {
        $page = (int)$page;
        if ($page === (int)$current) {
            return '<span class="pagination-link active">' . $page . '</span>';
        }
        return '<a class="pagination-link" href="' . $this->_esc($this->url($page)) . '">' . $page . '</a>';
    }

    function links($baseUrl = null, $window = 2)
    {
        if ($baseUrl !== null && $baseUrl !== '') { $this->baseUrl = $baseUrl; }
        if ($this->lastPage <= 1) { return ''; }
        $window = (int)$window;
        if ($window < 0) { $window = 0; }
        $cur = $this->currentPage;
        $last = $this->lastPage;

        $html = '<nav class="pagination" aria-label="Pagination">';

        if ($cur > 1) {
            $html .= '<a class="pagination-link pagination-prev" href="' . $this->_esc($this->url($cur - 1)) . '" rel="prev">&laquo; Prev</a>';
        } else {
            $html .= '<span class="pagination-link pagination-prev disabled">&laquo; Prev</span>';
        }

        $start = $cur - $window;
        if ($start < 1) { $start = 1; }
        $end = $cur + $window;
        if ($end > $last) { $end = $last; }

        if ($start > 1) {
            $html .= $this->_pageLink(1, $cur);
            if ($start > 2) { $html .= '<span class="pagination-ellipsis">&hellip;</span>'; }
        }

        $i = $start;
        while ($i <= $end) {
            $html .= $this->_pageLink($i, $cur);
            $i++;
        }

        if ($end < $last) {
            if ($end < $last - 1) { $html .= '<span class="pagination-ellipsis">&hellip;</span>'; }
            $html .= $this->_pageLink($last, $cur);
        }

        if ($cur < $last) {
            $html .= '<a class="pagination-link pagination-next" href="' . $this->_esc($this->url($cur + 1)) . '" rel="next">Next &raquo;</a>';
        } else {
            $html .= '<span class="pagination-link pagination-next disabled">Next &raquo;</span>';
        }

        $html .= '</nav>';
        return $html;
    }

    function toArray()
    {
        return array(
            'items' => $this->items,
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'from' => $this->from(),
            'to' => $this->to(),
            'has_more' => $this->hasMore(),
        );
    }
}
