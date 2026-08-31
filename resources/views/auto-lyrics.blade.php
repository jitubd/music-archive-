<!DOCTYPE html>
<html>
<head>
    <title>Music Archive - Auto Lyrics Fetch</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #0f0; padding: 20px; }
        #log { white-space: pre-wrap; line-height: 1.6; }
        .info { color: #0ff; }
        .error { color: #f00; }
        .done { color: #ff0; font-size: 1.2em; }
        h2 { color: #0ff; }
    </style>
</head>
<body>
    <h2>Music Archive - Auto Lyrics Fetch</h2>
    <div id="status">Starting lyrics fetch...</div>
    <div id="log"></div>

    <script>
        const secret = '{{ $secret }}';
        const log = document.getElementById('log');
        const status = document.getElementById('status');
        let batch = 0;
        let totalFound = 0;

        async function runBatch() {
            try {
                const res = await fetch(`/admin/fetch-lyrics?key=${secret}&batch=${batch}`);
                const data = await res.json();

                if (data.error) {
                    log.innerHTML += `<span class="error">ERROR: ${data.error}</span>\n`;
                    status.textContent = 'Lyrics fetch failed!';
                    return;
                }

                totalFound += data.found;
                log.innerHTML += `<span class="info">Batch ${data.batch}: checked ${data.checked}, +${data.found} found (${totalFound} total, ${data.remaining} remaining)</span>\n`;
                status.textContent = `Fetching lyrics... ${data.remaining} remaining, ${totalFound} found`;

                if (data.done) {
                    log.innerHTML += `<span class="done">\nDONE! Total lyrics found: ${totalFound}</span>\n`;
                    status.textContent = `Lyrics fetch complete! ${totalFound} lyrics found.`;
                } else {
                    batch = data.run_next.split('batch=')[1];
                    setTimeout(runBatch, 500);
                }
            } catch (e) {
                log.innerHTML += `<span class="error">NETWORK ERROR: ${e.message}. Retrying in 5s...</span>\n`;
                setTimeout(runBatch, 5000);
            }
        }

        runBatch();
    </script>
</body>
</html>
