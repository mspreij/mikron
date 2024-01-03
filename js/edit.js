document.addEventListener('DOMContentLoaded', function() {
    // focus on textarea when editing
    var ta = document.getElementById('editTextarea');
    if (ta && typeof ta.tagName !== 'undefined') {
        ta.focus();
    }else{
        console.error('No textarea found to focus (#editTextarea, edit.js)');
    }
});
