(function($) {
    $(document).ready(function() {
        // When a tile is clicked, navigate to its link URL
        $('.ft-wrap').on('click', '.ft-tile', function() {
            var href = $(this).data('href');
            if (href) {
                window.location.href = href;
            }
        });

        // Apply category-specific styles to each tile
        $('.ft-wrap .ft-tile').each(function() {
            var $this = $(this);

            // Retrieve style settings from data attributes
            var backgroundColor = $this.data('background-color');
            var textColor = $this.data('text-color');
            var h1Color = $this.data('h1-color');
            var h2Color = $this.data('h2-color');
            var pColor = $this.data('p-color');
            var fontFamily = $this.data('font-family');

            // Apply styles if they are set
            if (backgroundColor) $this.css('background-color', backgroundColor);
            if (textColor) $this.css('color', textColor);
            if (fontFamily) $this.css('font-family', fontFamily);

            // Apply text colors to specific elements within the tile
            $this.find('h1').css('color', h1Color);
            $this.find('h2').css('color', h2Color);
            $this.find('p').css('color', pColor);
        });
    });
})(jQuery);
