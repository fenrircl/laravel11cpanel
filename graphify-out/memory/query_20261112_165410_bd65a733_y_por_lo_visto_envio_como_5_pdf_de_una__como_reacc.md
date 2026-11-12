---
type: "query"
date: "2026-11-12T16:54:10.405292+00:00"
question: "y por lo visto envio como 5 pdf de una, como reacciona la api? puedes revisar su repo y ver si hay colas o como reacciona a eso"
contributor: "graphify"
source_nodes: ["api.php", "Queue Connections and Failed Jobs"]
---

# Q: y por lo visto envio como 5 pdf de una, como reacciona la api? puedes revisar su repo y ver si hay colas o como reacciona a eso

## Answer

Expanded from original query via vocab: [api, pdf, queue, job, worker, requests]. The Laravel graph connects api.php and queue configuration but does not contain the separate api-pdf repository. Direct inspection of /home/fenrir/api-pdf/app/main.py shows a synchronous FastAPI extraction request with an in-process asyncio.Semaphore defaulting to two active extractions; excess requests wait in memory and there is no durable job queue. Docker starts one Uvicorn worker. Five PDFs travel as separate n8n items and therefore separate API requests; the endpoint reads each upload before acquiring the semaphore. Defaults are 20 MB and 50 pages; OCR total timeout is 180 seconds and provider timeout is 60 seconds.

## Source Nodes

- api.php
- Queue Connections and Failed Jobs