document.addEventListener('DOMContentLoaded', function() {
    var cells = document.querySelectorAll('td');
    cells.forEach(function(cell) {
        if (cell.innerHTML.includes('&lt;button') || cell.innerHTML.includes('&quot;')) {
            var decoded = cell.innerHTML
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&quot;/g, '"')
                .replace(/&#039;/g, "'")
                .replace(/&amp;/g, '&');
            cell.innerHTML = decoded;
        }
    });
});
