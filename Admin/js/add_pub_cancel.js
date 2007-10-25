function cancelConfirm() {
    var fRet;
    result = confirm('Cancel all changes and stop adding / editing this publication entry?');
    if (result) {
        window.location = 'http://{host}{new_location}';
    }
}
