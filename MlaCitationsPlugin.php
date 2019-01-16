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
    /**
     * @var array Plugin filters.
     */
    protected $_filters = array('item_citation');

    /**
     * Filter the citation.
     * @param string $citation The citation text to be filtered (unused).
     * @param array $args Arguments provided to the filter.
     * @return string The filtered citation text.
     */
    public function filterItemCitation($citation, $args)
    {
        // Reset the citation to an empty string.
        $citation = '';

        // Obtain creators from DC Creator.
        $creators = metadata(
            $args['item'],
            array('Dublin Core', 'Creator'),
            array('all' => true)
        );

        // If there is no DC Creator, use the DC Contributors.
        if (!$creators) {
            $creators = metadata(
                $args['item'],
                array('Dublin Core', 'Contributor'),
                array('all' => true)
            );
        }

        // Clean up text, and then add creators to citation if available.
        $creators = array_filter(
            array_map('trim', array_map('strip_formatting', $creators))
        );

        if ($creators) {
            $citation .= $this->mlaPeriod($this->mlaCreators($creators)) . ' ';
        }

        // Obtain DC Title, and add to citation if available.
        $title = trim(strip_formatting(
            metadata($args['item'], array('Dublin Core', 'Title'))
        ));

        if ($title) {
            $citation .= '&#8220;' . $this->mlaPeriod($title) . '&#8221; ';
        }

        // Obtain site title from Omeka, and add to citation if available.
        $siteTitle = trim(strip_formatting(option('site_title')));

        if ($siteTitle) {
            $citation .= '<i>' . $this->mlaPeriod($siteTitle) . '</i> ';
        }

        // Obtain site author from Omeka.
        $author = trim(strip_formatting(option('author')));

        if ($author) {
            // If author is available, get the time the item was published.
            $published = strtotime($args['item']->modified);

            // If available, add the date published to the site author.
            if ($published > 0) {
                $author .= ', ' . $this->mlaDate($published);
            }

            // Get the URL to the item.
            $url = record_url($args['item'], null, true);

            // If the URL is available, add it to author without a protocol.
            if ($url) {
                $url = preg_replace('{^https?://}', '', $url);
                $author .= ', ' . $this->mlaPeriod($url);
            }

            // Add the author information to the citation.
            $citation .= $this->mlaPeriod($author) . ' ';
        }

        // Add the current date to the citation as the date accessed.
        $citation .= 'Accessed ' . $this->mlaPeriod($this->mlaDate(time()));
        return $citation;
    }

    /**
     * Format an array of creators into a MLA-style string.
     * @param array $creators An array of string creator names.
     * @return string The creators formatted into a string.
     */
    private function mlaCreators($creators)
    {
        switch (count($creators)) {
            // One creator is returned as-is.
            case 1:
                return $this->mlaNamePrimary($creators[0]);

            // Two creators are combined with an "and".
            case 2:
                return __(
                    '%1$s and %2$s',
                    $this->mlaNamePrimary($creators[0]),
                    $this->mlaNameSecondary($creators[1])
                );

            // Three creators are separated with commas and "and".
            case 3:
                return __(
                    '%1$s, %2$s, and %3$s',
                    $this->mlaNamePrimary($creators[0]),
                    $this->mlaNameSecondary($creators[1]),
                    $this->mlaNameSecondary($creators[2])
                );
        }

        // Four or more creators will display the first followed by "et al".
        return __(
            '%s et al',
            $this->mlaNamePrimary($creators[0])
        );
    }

    /**
     * Formats a time into an MLA-style date string.
     * @param integer $time The time to be formatted.
     * @return string The date formatted in MLA-style.
     */
    private function mlaDate($time)
    {
        // Get the full name of the month.
        $month = date('F', $time);

        // If the name is more than four characters, truncate it.
        if (strlen($month) > 4) {
            $month = substr($month, 0, 3) . '.';
        }

        // Return the day number, month abbreviation and four-digit year.
        return date('j ', $time) . $month . date(' Y', $time);
    }

    /**
     * Format the primary name in MLA-style.
     * @param string $name The name to format.
     * @return string The name formatted last name first.
     */
    private function mlaNamePrimary($name)
    {
        // Do nothing if the name doesn't appear to be a name.
        if (preg_match('/[^\d\s\w()\[\];:,.\/-]/', $name)) {
            return $name;
        }

        // Do nothing if the name already has a comma.
        if (strpos($name, ',') !== false) {
            return $name;
        }

        // Divide the names into parts.
        $parts = preg_split('/\s+/', $name);

        // If there is only one part, return it.
        if (sizeof($parts) < 2) {
            return $name;
        }

        // Return the last part followed my the rest.
        return array_pop($parts) . ', ' . implode(' ', $parts);
    }

    /**
     * Format a secondary name in MLA-style.
     * @param string $name The name to format.
     * @return string The name formatted first name first.
     */
    private function mlaNameSecondary($name)
    {
        // Do nothing if the name doesn't appear to be a name.
        if (preg_match('/[^\d\s\w()\[\];:,.\/-]/', $name)) {
            return $name;
        }

        // Do nothing if the name does not have a comma.
        if (strpos($name, ',') === false) {
            return $name;
        }

        // Divide the last part from the rest.
        $parts = preg_split('/,\s*/', $name, 2);

        // If there is only one part, return it.
        if (sizeof($parts) < 2) {
            return $name;
        }

        // Return the rest first.
        return $parts[1] . ' ' . $parts[0];
    }

    /**
     * Add a period to the end of a string if not already present.
     * @param string $text The text to add a period to.
     * @return string The text with a period at the end.
     */
    private function mlaPeriod($text)
    {
        if (substr($text, -1, 1) !== '.') {
            $text .= '.';
        }

        return $text;
    }
}
