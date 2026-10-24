// start jquery
$(document).ready(function() {
   


      $('.on_sort_catalog').on('change', function () {
            var val = $(this).val();
            const url = new URL(window.location);
            url.searchParams.set('catalog', val);
            history.pushState(null, null, url);
            location.reload();
      })














}) // end jquery