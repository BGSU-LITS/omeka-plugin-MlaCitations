<?php
/**
 * Omeka MLA Citations Plugin
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka MLA Citations Plugin: Plugin Class
 *
 * @package MlaCitations
 */
class MlaCitationsPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_filters = array('item_citation');

    public function filterItemCitation($citation, $args)
    {
        $citation = '';

        $creators = metadata(
            $args['item'],
            array('Dublin Core', 'Creator'),
            array('all' => true)
        );

        if (!$creators) {
            $creators = metadata(
                $args['item'],
                array('Dublin Core', 'Contributor'),
                array('all' => true)
            );
        }

        $creators = array_filter(
            array_map('trim', array_map('strip_formatting', $creators))
        );

        if ($creators) {
            $citation .= $this->mlaPeriod($this->mlaCreators($creators)) . ' ';
        }

        $title = trim(strip_formatting(
            metadata($args['item'], array('Dublin Core', 'Title'))
        ));

        if ($title) {
            $citation .= '&#8220;' . $this->mlaPeriod($title) . '&#8221; ';
        }

        $siteTitle = trim(strip_formatting(option('site_title')));

        if ($siteTitle) {
            $citation .= '<i>' . $this->mlaPeriod($siteTitle) . '</i> ';
        }

        $author = trim(strip_formatting(option('author')));

        if ($author) {
            $published = strtotime($args['item']->modified);

            if ($published > 0) {
                $author .= ', ' . $this->mlaDate($published);
            }

            $url = record_url($args['item'], null, true);

            if ($url) {
                $url = preg_replace('{^https?://}', '', $url);
                $author .= ', ' . $this->mlaPeriod($url);
            }

            $citation .= $this->mlaPeriod($author) . ' ';
        }


        $citation .= 'Accessed ' . $this->mlaPeriod($this->mlaDate(time()));
        return $citation;
    }

    private function mlaCreators($creators)
    {
        switch (count($creators)) {
            case 1:
                return $this->mlaNamePrimary($creators[0]);

            case 2:
                return __(
                    '%1$s and %2$s',
                    $this->mlaNamePrimary($creators[0]),
                    $this->mlaNameSecondary($creators[1])
                );

            case 3:
                return __(
                    '%1$s, %2$s, and %3$s',
                    $this->mlaNamePrimary($creators[0]),
                    $this->mlaNameSecondary($creators[1]),
                    $this->mlaNameSecondary($creators[2])
                );
        }

        return __(
            '%s et al',
            $this->mlaNamePrimary($creators[0])
        );
    }

    private function mlaDate($time)
    {
        $month = date('F', $time);

        if (strlen($month) > 4) {
            $month = substr($month, 0, 3) . '.';
        }

        return date('j ', $time) . $month . date(' Y', $time);
    }

    private function mlaNamePrimary($name)
    {
        if (preg_match('/[^\d\s\w()\[\];:,.\/-]/', $name)) {
            return $name;
        }

        if (strpos($name, ',') !== false) {
            return $name;
        }

        $parts = preg_split('/\s+/', $name);
        return array_pop($parts) . ', ' . implode(' ', $parts);
    }

    private function mlaNameSecondary($name)
    {
        if (preg_match('/[^\d\s\w()\[\];:,.\/-]/', $name)) {
            return $name;
        }

        if (strpos($name, ',') === false) {
            return $name;
        }

        $parts = preg_split('/,\s*/', $name, 2);
        return $parts[1] . ' ' . $parts[0];
    }

    private function mlaPeriod($text)
    {
        if (substr($text, -1, 1) !== '.') {
            $text .= '.';
        }

        return $text;
    }
}
