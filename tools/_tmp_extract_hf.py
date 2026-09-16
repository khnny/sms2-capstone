import json
import re
from pathlib import Path

path = Path(r"C:\Users\ASUS VIVOBOOK\.cursor\projects\c-xampp-htdocs-sms2-system\agent-transcripts\42c2895f-fd31-43c0-8ea8-a6f98e60ca8e\42c2895f-fd31-43c0-8ea8-a6f98e60ca8e.jsonl")
urls = set()
with path.open(encoding="utf-8") as f:
    for line in f:
        for m in re.finditer(r"https://hostforgeplatform\.cloud[^\"\\s<>]*", line):
            urls.add(m.group(0)[:240])
for u in sorted(urls):
    print(u)
