<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitter Archive Importer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; background-color: #f5f5f5; color: #333; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
        h1 { font-size: 1.5em; }
        #controls { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        #log-container { background-color: #222; color: #0f0; font-family: monospace; font-size: 0.9em; padding: 15px; border-radius: 4px; height: 400px; overflow-y: scroll; white-space: pre-wrap; }
        .log-entry { margin-bottom: 5px; }
        .log-error { color: #f00; }
        .log-success { color: #0f0; }
        .log-info { color: #888; }
        button { padding: 10px 15px; font-size: 1em; cursor: pointer; }
        input[type="file"] { margin-right: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Twitter Archive Importer</h1>
    <p>This tool will import your full Twitter archive (`tweets.js`) by processing the file in your browser and sending it to the server in small chunks, avoiding server upload limits and timeouts.</p>
    
    <div id="controls">
        <input type="file" id="archive-file" accept=".js">
        <button id="start-import">Start Import</button>
    </div>

    <div id="log-container"></div>
</div>

<script>
    const fileInput = document.getElementById('archive-file');
    const startButton = document.getElementById('start-import');
    const logContainer = document.getElementById('log-container');

    const CHUNK_SIZE = 50; // Process 50 tweets per request to avoid timeouts

    function log(message, type = 'info') {
        const entry = document.createElement('div');
        entry.className = `log-entry log-${type}`;
        entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        logContainer.appendChild(entry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    startButton.addEventListener('click', async () => {
        if (!fileInput.files || fileInput.files.length === 0) {
            log('Please select your tweets.js file first.', 'error');
            return;
        }

        startButton.disabled = true;
        startButton.textContent = 'Importing...';

        try {
            const file = fileInput.files[0];
            log(`Reading file: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)...`);

            const fileContent = await file.text();
            log('File read successfully. Parsing JSON data...');

            const jsonString = fileContent.substring(fileContent.indexOf('['));
            const allTweets = JSON.parse(jsonString);
            const totalTweets = allTweets.length;
            log(`Successfully parsed ${totalTweets} tweets.`, 'success');

            const totalChunks = Math.ceil(totalTweets / CHUNK_SIZE);
            log(`Splitting into ${totalChunks} chunks of up to ${CHUNK_SIZE} tweets each.`);

            let totalImported = 0;

            for (let i = 0; i < totalChunks; i++) {
                const chunk = allTweets.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                log(`Sending chunk ${i + 1} of ${totalChunks} (${chunk.length} tweets)...`);

                try {
                    const response = await fetch('api_receive_chunk.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            tweets: chunk,
                            is_first_chunk: i === 0
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`Server responded with status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.status === 'success') {
                        totalImported += result.imported_count;
                        log(`Chunk ${i + 1} processed. ${result.imported_count} new tweets were imported.`, 'success');
                    } else {
                        throw new Error(`Server error: ${result.message}`);
                    }

                } catch (e) {
                    log(`Error processing chunk ${i + 1}: ${e.message}`, 'error');
                    log('Aborting import process.', 'error');
                    return; // Stop the import
                }
            }

            log(`\n--------------------------------------------------`, 'success');
            log(`IMPORT COMPLETE!`, 'success');
            log(`Total new tweets imported into the database: ${totalImported}`, 'success');
            log(`You can now visit index.php to see your full archive.`, 'success');

        } catch (e) {
            log(`A critical error occurred: ${e.message}`, 'error');
        } finally {
            startButton.disabled = false;
            startButton.textContent = 'Start Import';
        }
    });

</script>

</body>
</html>
