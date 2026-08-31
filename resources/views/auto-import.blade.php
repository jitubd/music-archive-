<!DOCTYPE html>
<html>
<head>
    <title>Music Archive - Auto Import</title>
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
    <h2>Music Archive - Auto Import</h2>
    <div id="status">Starting import...</div>
    <div id="log"></div>

    <script>
        const secret = '{{ $secret }}';
        const log = document.getElementById('log');
        const status = document.getElementById('status');
        let batch = 0;
        let totalSongs = 0;

        async function runBatch() {
            try {
                const res = await fetch(`/admin/import?key=${secret}&batch=${batch}`);
                const data = await res.json();

                if (data.error) {
                    log.innerHTML += `<span class="error">ERROR: ${data.error}</span>\n`;
                    status.textContent = 'Import failed!';
                    return;
                }

                totalSongs += data.songs_imported;
                log.innerHTML += `<span class="info">Batch ${data.batch}: ${data.folders_processed} folders, +${data.songs_imported} songs (${totalSongs} total)</span>\n`;
                status.textContent = `Importing... ${data.folders_processed} folders, ${totalSongs} songs`;

                if (data.done) {
                    log.innerHTML += `<span class="done">\nDONE! Total songs imported: ${totalSongs}</span>\n`;
                    status.textContent = `Import complete! ${totalSongs} songs imported.`;
                } else {
                    batch = data.run_next.split('batch=')[1];
                    setTimeout(runBatch, 1000);
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
