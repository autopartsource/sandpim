# sandpim

SandPIM is a simple, LAMP-based Product Information Management system built around the AutoCare Association's ACES and PIES standards. The "Sand" in the name is a 
reference to the Sandpiper protocol that is starting to take shape in the AutoCare community (as of Fall, 2019). SandPIM serves a platform for testing concepts core to Sandpiper as they are being debated before adoption.
SandPIM is intentionally written with minimal layers of abstraction and without a third-party framework. This is to lower 
the barriers to entry for a casual experimenter or contributor. 

This project is in the early stages and is not ready for use in the real world. 
We are getting organized and developing our Git workflow. If you are interested in
contributing in any way (even just offering opinions!), please don't hesitate to reach out.

---

## Planned Features

- Catalog fitment management based on Make-Model-Year and/or Mfr-Equipment
- Digital Asset management with integration to AWS S3
- Part attribute (PAdb) management
- Competitor interchange management
- On-the-fly validation of data
- ACES & PIES xml exports
- ACES & PIES xml imports
- Sandpiper API server and client

--- 


## To-Do list

- selector page interface to put PCdb positions and parttypes into favorites tables.
- item-specific assets (show on the right side of showPart.php)
- vcdb-validation an app on the fly (uppon change)
- qdb fitmet in showApp.php
- copy-forward to next model-year on apps grid (showAppsByBasevehicle.php)
- fitment assets (show on the right side of showApp.php)
- export of ACES files
- export of PIES files
- user permission mechanism
- utility to duplicate apps from one basevehicle to another
- utility to duplicate apps from one partnumber to another
- fitment logic analysis on the fly (app grid page)
- background auditing and reporoting of parts
- background auditing and reporoting of apps
- API service to expose sandpiper objects
- API client features to consume sandpiper objects

