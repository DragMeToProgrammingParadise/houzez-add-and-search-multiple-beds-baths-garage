jQuery(document).ready(function($) {

        // Function to get query parameter value by name
        function getQueryParam(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
    
        // Extract sortby value from the URL and set it to the Load More button
        var sortbyFromUrl = getQueryParam('sortby');
        if (sortbyFromUrl) {
            $('.fave-load-more a').attr('data-sortby', sortbyFromUrl);
        }




        
    
       
    });
    

