<?php

// Main function called by shortcode that simply created the required html template
function googleRssCreate() {
    $searchFormHTML = '
        <form id="rss-search-form">
            <div class="rss-search-container">
                <input type="text" name="rss-search-input" id="rss-search-input" class="rss-search-input" placeholder="Search news..." />
                <input type="submit" value="Search" id="rss-search-submit" class="rss-search-submit" />
            </div>
        </form>
        <div id="rss-search-results"></div>
        <div id="rss-search-loading" style="display:none;">
            <div class="spinner"></div>
        </div>
    ';

    return $searchFormHTML;
}

// Function to fetch data from the API URL
function fetchData($api_url) {
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);

    if (empty($body)) {
        return false;
    }
    return $body;
}

// Function to parse the XML data
function parseXML($xml_string) {
    $xml = simplexml_load_string($xml_string);
    if ($xml === FALSE) {
        return false;
    }
    return $xml;
}

// AJAX handler to process the search and return results
function googleRssAjaxSearch() {
    if (isset($_POST['query'])) {
        $query = urlencode($_POST['query']);
        $api_url = 'https://news.google.com/news?q=' . $query . '&output=rss';

        $xml_data = fetchData($api_url);
        if (!$xml_data) {
            echo 'Error fetching data. Wait a 5 second and try again.';
            wp_die();
        }

        $xml = parseXML($xml_data);
        if (!$xml) {
            echo 'No news found';
            wp_die();
        }

        // Set a max amount of posts can parameterize through shortcode attributes. 
        // $limit = 10;
        // $count = 0;

        $output = '<ul>';
        foreach ($xml->channel->item as $item) {
            $count += 1;
            $output .= '<li><a href="' . esc_url($item->link) . '" target="_blank">' . esc_html($item->title) . '</a></li>';
            if($count == $limit){
                break;
            }
        }
        $output .= '</ul>';

        echo $output;
    }

    wp_die();
}

// Register Ajax action 
add_action('wp_ajax_google_rss_search', 'googleRssAjaxSearch');
add_action('wp_ajax_nopriv_google_rss_search', 'googleRssAjaxSearch');

// Javascript to enqueue to send run the Ajax action
function enqueue_rss_search_script() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('rss-search-form');
            const input = document.getElementById('rss-search-input');
            const resultsContainer = document.getElementById('rss-search-results');
            const loadingSpinner = document.getElementById('rss-search-loading');

            //Add the on submit event to search button
            form.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent the form from submitting normally

                const query = input.value;


                //While waiting for the new query display loading spinner and reset the resultsContainer
                loadingSpinner.style.display = 'block';
                resultsContainer.innerHTML = '';

                // Create a new AJAX request
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url("admin-ajax.php"); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                /// On Successful xml request
                xhr.onload = function() {
                    loadingSpinner.style.display = 'none'; // Hide the loading spinner
                    if (xhr.status === 200) {
                        resultsContainer.innerHTML = xhr.responseText;
                    } else {
                        console.error('Error:', xhr.statusText);
                    }
                };

                // On Failure
                xhr.onerror = function() {
                    loadingSpinner.style.display = 'none'; // Hide the loading spinner
                    console.error('Request failed');
                };

                // Send the AJAX request with the query data
                xhr.send('action=google_rss_search&query=' + encodeURIComponent(query));
            });
        });
    </script>
    <?php
}

// Register shortcode
function register_rss_search_shortcode() {
    add_shortcode('grss', 'googleRssCreate');
}
add_action('init', 'register_rss_search_shortcode');

//Enqueue the RSS Search Script 
add_action('wp_footer', 'enqueue_rss_search_script');
