// If user typed in authors not in the author table, then this function
// displays an alert to the user and lists all the names that are not in
// the author table.
function check_authors($form) {
    var form =  document.forms[$form];
    var authors = form.elements['authors'];

    // use comma as delimiter
    var collectionStr = ',' + collection.join(',') + ',';

    // strip comma and space at end of text area
    var paper_authors = authors.value.replace(/, *$/, '');

    if (paper_authors.length == 0) return true;

    var list = paper_authors.split(/, */);

    var notInDb = new Array();

    // for each author in text area find in collectionStr
    for (var i = 0; i < list.length; ++i) {
        var result = collectionStr.search(new RegExp(',' + list[i] + ',', 'i'));
        if (result == -1) {
            notInDb.push(list[i]);
        }
    }

    if (notInDb.length > 0) {
        var msg = 'The following author names are not in the database:\n\n' + notInDb.join(', ');
        msg += '\n\nPlease remove them from this list and add these authors to the database using the provided button.';
        alert(msg);

        return false;
    }
    return true;
}


