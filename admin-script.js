document.addEventListener('DOMContentLoaded', function() {
    var insertBtn = document.getElementById('listercleaner_load_sample_btn');
    var executeBtn = document.getElementById('listercleaner_ajax_execute_btn');
    var rawInput = document.getElementById('listercleaner_test_raw_input');
    var cleanOutput = document.getElementById('listercleaner_test_clean_output');

    if (insertBtn && rawInput) {
        insertBtn.addEventListener('click', function(e) {
            e.preventDefault();
            rawInput.value = '<script>console.log("bad script");</script>\n<style>body {color: red;}</style>\n<form action="http://evil.com">\n  <p>Product description details.</p>\n  <a href="http://my-wp-store.com">Local Safe Link</a>\n  <a href="http://external-buyer.com" onclick="doSomething()">External Link</a>\n</form>';
        });
    }

    if (executeBtn) {
        executeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var payload = {
                html: rawInput.value,
                _wpnonce: document.getElementById('listercleaner_security_token').value
            };

            executeBtn.disabled = true;
            executeBtn.value = ListerCleanerLoc.processing_text;

            wp.ajax.post('listercleaner_preview_sandbox', payload)
                .done(function(response) {
                    cleanOutput.value = response.cleaned_html;
                })
                .fail(function(error) {
                    alert(error || 'An unexpected verification failure occurred.');
                })
                .always(function() {
                    executeBtn.disabled = false;
                    executeBtn.value = ListerCleanerLoc.execute_text;
                });
        });
    }
});

