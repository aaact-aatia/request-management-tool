# Changelog

All notable changes to the Request Management Tool are documented here.

This project began as a snapshot of [aaact-aatia/rmt](https://github.com/aaact-aatia/rmt)
at tag `v2.0.0`, then diverged into its own history. Only work done since that
snapshot is listed below (the initial commit's restructuring is summarized as
the first entry).

Generated with [git-cliff](https://git-cliff.org) from conventional commit
messages — see [docs/CHANGELOG-WORKFLOW.md](docs/CHANGELOG-WORKFLOW.md) for
how to keep this up to date.

### Baseline

- Renamed and restructured the codebase for the Request Management Tool fork
(167 files touched vs. upstream `v2.0.0`): consolidated Azure Pipelines
configs, reworked `database/seed.sql`, and added the `docs/future/*` and
`docs/TEMPLATE_MIGRATION.md` planning docs
([4b9ebfa](https://github.com/aaact-aatia/request-management-tool/commit/4b9ebfa48636d306f14d06d39e1611af64c9a68d)).

### Added

- Replace dotenv with runtime env loading and fail-closed DB config ([8623060](https://github.com/aaact-aatia/request-management-tool/commit/86230609a07124d0f0359072326c32091ad8b867))

- Route landing by auth state ([2ddd532](https://github.com/aaact-aatia/request-management-tool/commit/2ddd532f028ef7f8997f76763ca6497f73782d6e))

- Migrate request dashboards to shared card grid ([aa81619](https://github.com/aaact-aatia/request-management-tool/commit/aa816191538ce3b24c87568fe5ebc0a2ffcdccdc))

- Update closed requests dashboard labels and card metadata ([eca732b](https://github.com/aaact-aatia/request-management-tool/commit/eca732be4ba469ccad55a10bfa44f5321339c8d5))

- Add card sorting controls to overview and closed pages ([71f4602](https://github.com/aaact-aatia/request-management-tool/commit/71f460268f4a2cb6f8d518e141e567bee1550350))

- Add department field to request router sample data ([0eaa1ec](https://github.com/aaact-aatia/request-management-tool/commit/0eaa1ecf995257e6f0417cc469ba8103d65e439e))

- Remove URL attachment inputs from step 2 form ([04313b9](https://github.com/aaact-aatia/request-management-tool/commit/04313b944c3ccf1711dd693f63c4fe9cf4186646))

- Standardize admin table layouts across all management pages ([0695563](https://github.com/aaact-aatia/request-management-tool/commit/06955630b138ef97a1e19d6e5d7900389039d543))

- Append readable reqid query param to all viewrequest URLs ([f826635](https://github.com/aaact-aatia/request-management-tool/commit/f8266359995f9b9df09e117ac62874f07c7698f1))

- Add survey sent/answered badges and filter to closed requests page ([5ed859a](https://github.com/aaact-aatia/request-management-tool/commit/5ed859a07cc0bdf2b4ee12b8324ad2a2e64a147a))

- Add survey badges and filter to my requests page ([ce5a688](https://github.com/aaact-aatia/request-management-tool/commit/ce5a688dd7a86223cfdf28fe5f6ce11184038e25))

- Add custom bilingual 404 page ([b02d11d](https://github.com/aaact-aatia/request-management-tool/commit/b02d11dffc3c5463e6035e22aebd55ddb17599a6))

- Phase 1 refTop CDTS-docwrite removal ([80600c1](https://github.com/aaact-aatia/request-management-tool/commit/80600c16de6ec0799b3f3bc98f08579e8d696ea6))

- Merge appTop into shared include ([98f517f](https://github.com/aaact-aatia/request-management-tool/commit/98f517f17a3cf5ed13a0a661f229ff7df2a89afa))

- Align openrequest with shared templates ([d28e4c3](https://github.com/aaact-aatia/request-management-tool/commit/d28e4c3be2bb08525a751b1255da5698b82f71ef))

- Complete step-one template migration cleanup ([ae7c17d](https://github.com/aaact-aatia/request-management-tool/commit/ae7c17d91206bc38b72c1867be3fce770e442370))

- Add admin-configurable resolved status trigger for survey flow ([0615cb5](https://github.com/aaact-aatia/request-management-tool/commit/0615cb55581716af21f69dbc84140b21d12ac402))

- Centralize resolved status handling ([aa3225f](https://github.com/aaact-aatia/request-management-tool/commit/aa3225fbefe79d62de0f4f5af8bdb447dfa6f5d2))

- Standardize modal actions and align request detail groupings ([2631fa7](https://github.com/aaact-aatia/request-management-tool/commit/2631fa732bf31e45ac12f0637c96858cdf7371c2))

- Add team hierarchy and optional lead assignment ([d33fe86](https://github.com/aaact-aatia/request-management-tool/commit/d33fe8676236c6622836ab4c20c7df7584c06148))

- Relax employee team assignment rules ([21408f0](https://github.com/aaact-aatia/request-management-tool/commit/21408f0310e8f6c6b313ef645bd48a77922a36ae))

- Add team details page and role-based team editing ([71b7622](https://github.com/aaact-aatia/request-management-tool/commit/71b7622dec127ed9a231d2284e21663dd70fa120))

- Expand team details with contact and escalation fields ([a144f29](https://github.com/aaact-aatia/request-management-tool/commit/a144f293e754cacf2f9d8688999a1ab34f659b55))

- Add extra superuser/admin permissions for manager-teamlead roles ([c1ce842](https://github.com/aaact-aatia/request-management-tool/commit/c1ce842c3b890062d26cc97575cd808c55a87f8e))

- Add CSV export/import to admin management pages ([f11cf53](https://github.com/aaact-aatia/request-management-tool/commit/f11cf5307ada07dfe7f1dc7040b0c372971d67ee))

- Implement MySQL-backed PHP sessions with centralized bootstrap ([126fe00](https://github.com/aaact-aatia/request-management-tool/commit/126fe00547265ebb08d87a5ec08d764dad26d1d7))

- Add dynamic entry count and flexbox styling for search results ([d2690cf](https://github.com/aaact-aatia/request-management-tool/commit/d2690cfb973061aacac96b18acda8f41b2e4a817))

- Add removeEmptyFields JavaScript utility for clean form submission ([4e2201a](https://github.com/aaact-aatia/request-management-tool/commit/4e2201ad54e4d37f17ca148c2eca20d790e5c547))

- Add environment-aware GC Notify delivery controls ([5bada5b](https://github.com/aaact-aatia/request-management-tool/commit/5bada5bff591c19e5270826343ddcc1688b605cd))

- Externalize GC Notify config and harden dev redirects ([b02c010](https://github.com/aaact-aatia/request-management-tool/commit/b02c010585a1dc613f0845797b3548e3e0d5a8f9))

- Checkpoint GC Notify settings and docs ([d0d810b](https://github.com/aaact-aatia/request-management-tool/commit/d0d810bd2293896df1b8c50425a0c78bc8975407))

- Store request language for notifications ([ecdf509](https://github.com/aaact-aatia/request-management-tool/commit/ecdf509966ac60e5114e4b0b15cbd67bd019aaf6))

- Add quick test request helper ([bf8ee63](https://github.com/aaact-aatia/request-management-tool/commit/bf8ee6391c0f1c2a664c1bf817ead2d8a6849cd4))

- Move quick test request into superuser menu ([fd4b702](https://github.com/aaact-aatia/request-management-tool/commit/fd4b702b18c30c8ff0ca238f2d7766913771e898))

- Finalize bilingual generic notify messaging ([3743c46](https://github.com/aaact-aatia/request-management-tool/commit/3743c46230d195d0c18475892185f3ac78eec12c))

- Make resolved client notifications fully manual ([a68cb46](https://github.com/aaact-aatia/request-management-tool/commit/a68cb465344733d6ea2e990df61fa1e1d2756270))

- Add survey thank-you page and return link ([2d9b3bb](https://github.com/aaact-aatia/request-management-tool/commit/2d9b3bb33ebdca8fa68fa82de9a84b368af03e08))

- Add bilingual closing signature with optional team name ([eb42077](https://github.com/aaact-aatia/request-management-tool/commit/eb4207737e5afd2fbe60456778d47cca55105867))

- Enforce role policies for manager and team lead workflows ([dce8c79](https://github.com/aaact-aatia/request-management-tool/commit/dce8c79b6e4f307619518c9fb733b232137581ec))

- Finalize team-lead scope and implement employee-scoped workflow ([07d6bc4](https://github.com/aaact-aatia/request-management-tool/commit/07d6bc48b117a34bc4a489c72f277bc40d3c63e2))

- Add admin docs browser from markdown ([26656c7](https://github.com/aaact-aatia/request-management-tool/commit/26656c7ae0a9edf18cf74a603ed3f0d86542fbfe))

- Harden markdown docs pages ([d3ba3e7](https://github.com/aaact-aatia/request-management-tool/commit/d3ba3e74484eeaabd81cc6aa0bff1f164620a9d7))

- Add workflow and request change audit logs ([e39c4cc](https://github.com/aaact-aatia/request-management-tool/commit/e39c4ccfa61973babc1034e51d45e5a60b5a23e2))

- Implement end-to-end upload/download hardening and upload-only UX ([b1cfdd9](https://github.com/aaact-aatia/request-management-tool/commit/b1cfdd9af53172f98679b10fe1b0d56502482c5e))

- Add FILE_STORAGE_MODE=disabled to gate file uploads ([2d5f606](https://github.com/aaact-aatia/request-management-tool/commit/2d5f606e1d7c7b42cae585a62d68837704662245))

- DB-driven open request cascade with catalogue config flags ([8dd7288](https://github.com/aaact-aatia/request-management-tool/commit/8dd728842137f1d57609f3ac4acfc064d70ff5b8))

- Add configurable intake flow foundation ([08f88a7](https://github.com/aaact-aatia/request-management-tool/commit/08f88a7f0a335432a7f62415876ee06d66c7c499))

- Add portable intake workflow seed tooling ([018a41f](https://github.com/aaact-aatia/request-management-tool/commit/018a41f32beec21cc59f84a8ca0e2dcfe11548c8))

- Manage active catalogue hierarchy ([874a95a](https://github.com/aaact-aatia/request-management-tool/commit/874a95a21f0a0b7a2f32c278311d4cb82ab6164b))

- Add isolated database seed profiles ([c6e9090](https://github.com/aaact-aatia/request-management-tool/commit/c6e90903f83deb0aea0ccbf1c46cd3232ca2392b))

- Add priority scoring engine ([1aa8fcf](https://github.com/aaact-aatia/request-management-tool/commit/1aa8fcfaa241237b0cbed5d843e4638cb7367cbc))

- Add managed organization directory ([7cfbfe5](https://github.com/aaact-aatia/request-management-tool/commit/7cfbfe5afd7615e6671408e46ffd780d98eb87c2))

- Update links to use lbx-modal for lightbox modals across multiple files ([25a4e2e](https://github.com/aaact-aatia/request-management-tool/commit/25a4e2ec9e85328abbf16e302917a7a24b92fdae))

- Add private local seed workflow ([628de14](https://github.com/aaact-aatia/request-management-tool/commit/628de146e1727afd4a9a61bdb9c7191bf76428fb))

- Add configurable request subjects ([d531ada](https://github.com/aaact-aatia/request-management-tool/commit/d531ada6e735052d4c1a20e394792860ac182e05))

- Inherit responsible teams ([5d4b29a](https://github.com/aaact-aatia/request-management-tool/commit/5d4b29abcf06c410b666be08208208c05addf804))

- Link overview cards by title ([11dcfcd](https://github.com/aaact-aatia/request-management-tool/commit/11dcfcd2e5287142a957280fe599b2fb66c6142d))

- Clarify intake hierarchy questions ([d0d575d](https://github.com/aaact-aatia/request-management-tool/commit/d0d575dd4c9d4fcf9fd7e67cf6c4b0c72c26b51e))

- Move intake details out of communications ([2574676](https://github.com/aaact-aatia/request-management-tool/commit/2574676e72f1886889a8069ffbae57569c5b5718))

- Add per-team/service notification message templates ([681657d](https://github.com/aaact-aatia/request-management-tool/commit/681657de501674c0aad49fc64482b6d3c8c76ce4))

- Rework notification template editing UX and previews ([2d0a154](https://github.com/aaact-aatia/request-management-tool/commit/2d0a154a0a31e7bf09313325e55b8a10c8b2d064))

- Add per-team notification controls ([c24158c](https://github.com/aaact-aatia/request-management-tool/commit/c24158c4d2e01413c51c21cabcd8cd8d0841bcf3))

- Enable persistent attachment storage ([67912c7](https://github.com/aaact-aatia/request-management-tool/commit/67912c72a44d8bf91719d4697d53efb6a49ebd32))

- Name attachment archives by request ([40188ee](https://github.com/aaact-aatia/request-management-tool/commit/40188ee0f210c3321ef400599f3575d69d634483))

- Permanently delete catalogue items ([8bd9aaa](https://github.com/aaact-aatia/request-management-tool/commit/8bd9aaace9616208acc1628e5c03a8394422441a))

- Enable role-scoped file deletion ([f47ea6f](https://github.com/aaact-aatia/request-management-tool/commit/f47ea6f48eb90bf3dd05edf14b04921b9ff0c176))

- Improve role-based request overviews ([97a6ab9](https://github.com/aaact-aatia/request-management-tool/commit/97a6ab9c77de5e295c20dc8c631ac5c439b94175))

- Add manager team scope testing ([0a16a8e](https://github.com/aaact-aatia/request-management-tool/commit/0a16a8e95bfa642db9dc4a916add2d632b40afa8))

- Consolidate request dashboard ([64ce707](https://github.com/aaact-aatia/request-management-tool/commit/64ce7070d72a74528794269398e85e3d2c2213e3))

- Reorganize advanced search form ([272f7bd](https://github.com/aaact-aatia/request-management-tool/commit/272f7bdce677d461e20e006564c46c73380195c2))

- Scope employee search options ([978063e](https://github.com/aaact-aatia/request-management-tool/commit/978063e2a3e2942fd92f9a3031a8ffb1bfcb5a89))


### Build

- Package app for azure container runtime ([b65c7c8](https://github.com/aaact-aatia/request-management-tool/commit/b65c7c86df88b2834dac27ab48d5c95873fa21c2))


### CI/CD

- Replace zip-deploy workflow with GHCR build-and-push ([eadc80c](https://github.com/aaact-aatia/request-management-tool/commit/eadc80c86c6ca8d87694bee877ade195e3696a7c))

- Rename workflow to publish-container.yml ([c454992](https://github.com/aaact-aatia/request-management-tool/commit/c454992ed6d2aad3df7f9d0be42d30956dd0b721))

- Remove obsolete dev Azure Web App workflow ([5e7bbc7](https://github.com/aaact-aatia/request-management-tool/commit/5e7bbc740603451573637778e6c4166ab95bb7fd))

- Restart dev app service after container publish ([ef95114](https://github.com/aaact-aatia/request-management-tool/commit/ef95114294a0dcf3118b54e59ff1b9ce0cebda78))

- Restart prod app service after main publish ([2316030](https://github.com/aaact-aatia/request-management-tool/commit/2316030b74bb683227b93c8e6162fd3c013a8eeb))

- Skip azure restart when config is missing ([ad8b3bb](https://github.com/aaact-aatia/request-management-tool/commit/ad8b3bbad4eee735f454d9f608ca77393ad1e734))

- Auto-regenerate CHANGELOG.md on push to main ([be9a88b](https://github.com/aaact-aatia/request-management-tool/commit/be9a88b389fca5a62d80f89d6ba99ae6eb92265b))


### Changed

- Remove BDM options from request flows and UI ([9e6656f](https://github.com/aaact-aatia/request-management-tool/commit/9e6656f05734496c1f307eb063ccac61c0594977))

- Complete contacts-to-teams migration with schema expansion and backward-compatibility layer ([934f16f](https://github.com/aaact-aatia/request-management-tool/commit/934f16f4e8c2995b675c253c809ba397d8d5507e))

- Rename ITAO labels/keys to staff and AAACT/AATIA terms ([0550392](https://github.com/aaact-aatia/request-management-tool/commit/05503925fa7afc39654cc04969442dcc3a2e3562))

- Refactor client survey naming and fix generated survey links ([64a523c](https://github.com/aaact-aatia/request-management-tool/commit/64a523cef541c5e0537e8cb0f7b812e3372dd752))

- Localize survey and communications text on request details ([981553b](https://github.com/aaact-aatia/request-management-tool/commit/981553b6b2304189d2e5695ca6f1380249d70460))

- Improve edit request form structure and image preview behavior ([0be6734](https://github.com/aaact-aatia/request-management-tool/commit/0be6734d724f9566582d58dc8bc23f0fed0b2fc5))

- Convert survey filter dropdown to checkboxes on resolved page ([94b50a6](https://github.com/aaact-aatia/request-management-tool/commit/94b50a6915498a3d41b3246538376aef04910e36))

- Implement GCWeb checkbox styling (gc-chckbxrdio) across app ([e712905](https://github.com/aaact-aatia/request-management-tool/commit/e712905dc8d0f866036e7282ff861760a416615e))

- Convert all checkbox lists to horizontal layout using list-inline ([108e594](https://github.com/aaact-aatia/request-management-tool/commit/108e5947fc73c13cba089e08af03544ae3ad1e19))

- Move inline JS into public script files ([44c4c92](https://github.com/aaact-aatia/request-management-tool/commit/44c4c92a9597827bc71a5854e39f366e83755572))

- Clean holiday form markup and formatting ([a6ad89c](https://github.com/aaact-aatia/request-management-tool/commit/a6ad89ce5b9b393e7fee80cd25562918827a11af))

- Remove stale manager form handling from user flows ([56b9e9f](https://github.com/aaact-aatia/request-management-tool/commit/56b9e9f768c63ee1802f5fc6f55e4a39cee3dd32))

- Show unassigned label for employee team lead relationship ([f83c811](https://github.com/aaact-aatia/request-management-tool/commit/f83c81177585907672f4a44c49439433852e170f))

- Standardize displayed names to first-last order ([460cf2a](https://github.com/aaact-aatia/request-management-tool/commit/460cf2ad6012da95972a3f345c9494b532be1a69))

- Reordered rating to be 10 first in client survey form ([7f67dfa](https://github.com/aaact-aatia/request-management-tool/commit/7f67dfa90b4eb4b5ce1c5ca87ff6849c85f10695))

- Simplify tblteams - remove manager_user_id, rely on tblusers.team assignment ([a052cfe](https://github.com/aaact-aatia/request-management-tool/commit/a052cfee1b8b012ff2e93f8f82dfe2291e0ae495))

- Convert search form to GET with clear button ([d595fb1](https://github.com/aaact-aatia/request-management-tool/commit/d595fb15b5fd778ff99fd879c0139c1ded209d0a))

- Remove unused environment switching feature ([a38eebc](https://github.com/aaact-aatia/request-management-tool/commit/a38eebc04c1b462adba1b87eb31752bb73d1d29e))

- Convert permission system from hard-coded account types to permission flags ([9d99b41](https://github.com/aaact-aatia/request-management-tool/commit/9d99b41f92a9b8b978dd1d976f3bb551e118d3de))

- Remove legacy newrequest field from edit flow ([7fde539](https://github.com/aaact-aatia/request-management-tool/commit/7fde539ef3ac50ad531d4e60a024c895e7e324f1))

- Remove audit type selection ([b5ae85e](https://github.com/aaact-aatia/request-management-tool/commit/b5ae85e4124a880650f9572fb446d8a11a52a2be))

- Remove public upload option ([793e6b2](https://github.com/aaact-aatia/request-management-tool/commit/793e6b21019489af09e1eb434d7325f99a8e5446))

- Simplify public contact fields ([cdb4f56](https://github.com/aaact-aatia/request-management-tool/commit/cdb4f56ff1edad66eeb4d4b1341c055df48ae515))

- Update edit request buttons ([ccf4561](https://github.com/aaact-aatia/request-management-tool/commit/ccf4561249a914b1229b1de335db3a46cf85ce88))

- Expand form controls to full width ([3311069](https://github.com/aaact-aatia/request-management-tool/commit/331106922d6e1e702fbe94b46f8d55e4e7883c0d))


### Chore

- Remove public repo secret artifacts ([3610da8](https://github.com/aaact-aatia/request-management-tool/commit/3610da87918a59d649775f29d48c686c1fbb5c29))

- Align catalogue/service seeds with request-first routing ([0d6b9e1](https://github.com/aaact-aatia/request-management-tool/commit/0d6b9e1e61ac3286aa4f13ab8b257f8ff07b6375))

- Remove legacy template shim includes ([d5708f6](https://github.com/aaact-aatia/request-management-tool/commit/d5708f6bd2cbd4fdc82154dbcd7fb789f2196c73))

- Remove CDTS asset dependencies from shared templates ([0b1cb5e](https://github.com/aaact-aatia/request-management-tool/commit/0b1cb5ed2b33c3be7f18906a91babac35cfef25e))

- Migrate error-page to shared WET assets ([bf68411](https://github.com/aaact-aatia/request-management-tool/commit/bf684115fd46ed0e56af3d98bfab822f79ffb9a0))

- Remove stale CDTS comment from rmt.css ([0755fcd](https://github.com/aaact-aatia/request-management-tool/commit/0755fcd54fa08c8b1d93a7a1f5edc06db97c4b51))

- Remove legacy seed.sql references ([b36867f](https://github.com/aaact-aatia/request-management-tool/commit/b36867f29406e1b704437ee63510b74eab27553c))

- Remove legacy appmenu include ([7be818b](https://github.com/aaact-aatia/request-management-tool/commit/7be818bfece950950411ffe9cd049cbc235df3a8))

- Disable edit file uploads until storage is implemented ([962d5ea](https://github.com/aaact-aatia/request-management-tool/commit/962d5ea068501be46db899aba0ca1d5d7672de43))

- Disable sprint spot-check fields and document for future reference ([950a1a7](https://github.com/aaact-aatia/request-management-tool/commit/950a1a70d65013c98d441ca4af0ab97eaeb0bd66))


### Database

- Split seed into schema, reference, and sample-dev ([002ce8a](https://github.com/aaact-aatia/request-management-tool/commit/002ce8ab7f8e5164de6b49d5eba4eb7291e55b46))

- Use split seed files for local Docker init ([5ebebec](https://github.com/aaact-aatia/request-management-tool/commit/5ebebec71ee53faf76d8a71b1c41c91e53778095))


### Documentation

- Update copilot-instructions for request-management-tool ([ca128da](https://github.com/aaact-aatia/request-management-tool/commit/ca128da01d8a39b134fb8bae9ba5feb367d83b42))

- Update README with container deployment and Azure App Service config ([913e11d](https://github.com/aaact-aatia/request-management-tool/commit/913e11dd9f89d4e68f6c469e4785f7d738b21ee0))

- Document Azure container deployment workflow ([ff5dc56](https://github.com/aaact-aatia/request-management-tool/commit/ff5dc5660140ad3f32ecc95930bf25cb080bc882))

- Require Docker for PHP validation ([d9776ba](https://github.com/aaact-aatia/request-management-tool/commit/d9776bab4a4aaf68985e3a163d403747ae33b879))

- Plan superadmin bulk anonymization ([d5b126b](https://github.com/aaact-aatia/request-management-tool/commit/d5b126b2c9356666c3adec141cd639bfb18dd07f))

- Add permissions model and guest access policy ([da61b0c](https://github.com/aaact-aatia/request-management-tool/commit/da61b0cf3db4c59a422efb02f0fc26cdb01146e0))

- Define target permissions and route access map ([d0adb1f](https://github.com/aaact-aatia/request-management-tool/commit/d0adb1f06880ce7a411bbdb220aa3f94bcbfb8dd))

- Add role-specific edit field policy ([1b46601](https://github.com/aaact-aatia/request-management-tool/commit/1b46601d8905ce40fa5942b0cd5393c4a8537b58))

- Finalize director read-only policy behavior ([4907763](https://github.com/aaact-aatia/request-management-tool/commit/4907763ad19b696797b4de5249b6814868918477))

- Show effective employee in testing banner ([d8c632f](https://github.com/aaact-aatia/request-management-tool/commit/d8c632fb6dcb283689b113fb143204646957bd7c))

- Rebaseline permissions docs to current dev state ([8e8e17e](https://github.com/aaact-aatia/request-management-tool/commit/8e8e17e3b81c67d7d012546b3a1c2c06d0b5a64b))

- Add lightweight configuration management pack ([8b5888a](https://github.com/aaact-aatia/request-management-tool/commit/8b5888ae60152479bd09967f5e229fbc98f5d937))

- Add README links for config-management docs ([3ec805c](https://github.com/aaact-aatia/request-management-tool/commit/3ec805cf63c824c4c48e59a9465a444784440970))

- Complete config management baseline follow-through ([c786037](https://github.com/aaact-aatia/request-management-tool/commit/c786037eb72c38b299afa1561ac87b77a333183e))

- Document database-driven intake flow ([ec5b8fe](https://github.com/aaact-aatia/request-management-tool/commit/ec5b8fe9fc89444f202bfd3b257c67177efad7cc))

- Record priority workflow completion ([5c52695](https://github.com/aaact-aatia/request-management-tool/commit/5c5269551ff46cb7b2cc9ec8cf522ce5c32d93b9))

- Add generated changelog and workflow ([98ba883](https://github.com/aaact-aatia/request-management-tool/commit/98ba883cef1afc88e680ca8905489f14153b6f5a))

- Update changelog for service SLA fix ([11ca693](https://github.com/aaact-aatia/request-management-tool/commit/11ca6934eb422712b02acd5db4bf3dcfc86318ec))

- Update changelog for team label change ([d7612af](https://github.com/aaact-aatia/request-management-tool/commit/d7612af3e4c8c4ff4d3437dade69dd1a9357a0e7))

- Regenerate changelog ([ef20232](https://github.com/aaact-aatia/request-management-tool/commit/ef20232aa574737ba6ae54a0c989070074248070))

- Regenerate CHANGELOG.md [skip ci] ([2306c54](https://github.com/aaact-aatia/request-management-tool/commit/2306c54b366a9705d831157c0e2b299c0a1b9c96))

- Regenerate CHANGELOG.md [skip ci] ([e798d8f](https://github.com/aaact-aatia/request-management-tool/commit/e798d8f55ba6052e26c78be25268ca05d7e83385))

- Regenerate CHANGELOG.md [skip ci] ([597cb14](https://github.com/aaact-aatia/request-management-tool/commit/597cb141b01caedb96801c7fb05a15c65192a6cf))

- Regenerate CHANGELOG.md [skip ci] ([520066d](https://github.com/aaact-aatia/request-management-tool/commit/520066dbe3b627a6dda995114c53476b35dec875))

- Regenerate CHANGELOG.md [skip ci] ([9d419aa](https://github.com/aaact-aatia/request-management-tool/commit/9d419aa979fe4529d5b802a776ccb078ef1a1404))

- Regenerate CHANGELOG.md [skip ci] ([bf04d8b](https://github.com/aaact-aatia/request-management-tool/commit/bf04d8be7980b33b75508f28e48e7dc0297e8a27))

- Regenerate CHANGELOG.md [skip ci] ([75bf33b](https://github.com/aaact-aatia/request-management-tool/commit/75bf33bd8b4464f101a8c79f3e19e7ca219e6efd))

- Regenerate CHANGELOG.md [skip ci] ([56cc523](https://github.com/aaact-aatia/request-management-tool/commit/56cc5236af16b1a0f0027364bebb13bb81587cd1))

- Regenerate CHANGELOG.md [skip ci] ([ffd3cf5](https://github.com/aaact-aatia/request-management-tool/commit/ffd3cf5057a3271c9596addea3b93509ff1f3bb0))

- Regenerate CHANGELOG.md [skip ci] ([9d0dd4c](https://github.com/aaact-aatia/request-management-tool/commit/9d0dd4cc8b0d5a4aa2c49378a12f204defe328a8))

- Regenerate CHANGELOG.md [skip ci] ([1310a4f](https://github.com/aaact-aatia/request-management-tool/commit/1310a4ffa114bf5a09b3ea89bec295a6b1a0ca26))

- Regenerate CHANGELOG.md [skip ci] ([5d56caf](https://github.com/aaact-aatia/request-management-tool/commit/5d56caffd4fa6dce6ba82964f00d247483122481))

- Regenerate CHANGELOG.md [skip ci] ([603bf94](https://github.com/aaact-aatia/request-management-tool/commit/603bf945ce2535557395250d4f9d297749731eb3))

- Regenerate CHANGELOG.md [skip ci] ([b4069f9](https://github.com/aaact-aatia/request-management-tool/commit/b4069f9da7e4f94dd7c3e384cee4fb48ba024db9))

- Regenerate CHANGELOG.md [skip ci] ([f027b3a](https://github.com/aaact-aatia/request-management-tool/commit/f027b3a2dfc1b9a827941b4f2fda03439b0b25f4))

- Regenerate CHANGELOG.md [skip ci] ([74b1199](https://github.com/aaact-aatia/request-management-tool/commit/74b119967fc2006a0545e75494b73e6030149815))

- Regenerate CHANGELOG.md [skip ci] ([cb90d46](https://github.com/aaact-aatia/request-management-tool/commit/cb90d46fe68bdc86478897eab8b8dc71d5212a86))


### Fixed

- Harden session bootstrap and date parsing ([8e6e072](https://github.com/aaact-aatia/request-management-tool/commit/8e6e0726bd710a968abaf9d3b9231d3790c9f251))

- Add vendor_data named volume to prevent bind mount shadowing vendor/ ([efad8a0](https://github.com/aaact-aatia/request-management-tool/commit/efad8a0a4725e8bae9c5faa2ac6a5493fd0d35d2))

- Show department/agency in request details and edit ([a61d904](https://github.com/aaact-aatia/request-management-tool/commit/a61d904333e79351546199262f587930f45248ec))

- Keep department separate from additional info notes ([cd8bf81](https://github.com/aaact-aatia/request-management-tool/commit/cd8bf81d19a62c37bfeb9a3090a541f03571ee38))

- Preserve request page lang and hide urgent review alert ([256ed80](https://github.com/aaact-aatia/request-management-tool/commit/256ed800a0b2f03f84d265300362aa7453b9142b))

- Let signed-in users open new request ([25fc892](https://github.com/aaact-aatia/request-management-tool/commit/25fc892ca54c44197841d182de884e7739771217))

- Remove remaining project type dropdown in 8:2 flow ([782d60a](https://github.com/aaact-aatia/request-management-tool/commit/782d60a94f4c6dc69a6ce2803cb4b1988c2de420))

- Use language array in catalogue sub-management actions ([e1de2e3](https://github.com/aaact-aatia/request-management-tool/commit/e1de2e33b6d34c33063eb6a37c14337376fb3f7f))

- Hide edit controls for unauthenticated request viewers ([4f3b217](https://github.com/aaact-aatia/request-management-tool/commit/4f3b217e675b640312274eff0ae666f337de9692))

- Reduce false undefined $link diagnostics ([2e929d5](https://github.com/aaact-aatia/request-management-tool/commit/2e929d586a0b27a1bdf6a2907affbc091674a8b9))

- Update breadcrumb org name and links ([2726c1a](https://github.com/aaact-aatia/request-management-tool/commit/2726c1a487256b64daa832d5b487ec0356b096c5))

- Implement hard-delete for users and teams with orphan user warning ([93b34ce](https://github.com/aaact-aatia/request-management-tool/commit/93b34cebdb1a3affe93e4de89d68f3e5a1eff86d))

- Pass lang param to edit/delete include links; list orphaned users by name in team delete warning ([cf32c03](https://github.com/aaact-aatia/request-management-tool/commit/cf32c03e7a2250e3684564f89b071879be083643))

- Hide urgent review message for unauthorized users ([55029cb](https://github.com/aaact-aatia/request-management-tool/commit/55029cbd4c1f616debe7986598ec70a428931e51))

- Allow editing requests without a selected service ([56fe254](https://github.com/aaact-aatia/request-management-tool/commit/56fe254f6a706607be6d8980187b509f665a80f8))

- Make Department/agency field optional on new request form ([76b0654](https://github.com/aaact-aatia/request-management-tool/commit/76b06548c2bcd086b36deb03a5f7ec15a92bc75d))

- Open catalogue service pages outside lightbox ([11ae0a1](https://github.com/aaact-aatia/request-management-tool/commit/11ae0a1a55be33fb1424518dddaf659b59832116))

- Add lang and reqid params to all editrequest.php links ([4055e4c](https://github.com/aaact-aatia/request-management-tool/commit/4055e4cf903b9b81ca7b73a20da43e59b7c58550))

- Suppress SLA warnings for terminal statuses ([db18ee4](https://github.com/aaact-aatia/request-management-tool/commit/db18ee48025c3fc060db45dafe0dc19359923afa))

- Derive page modified date from file mtime ([457558d](https://github.com/aaact-aatia/request-management-tool/commit/457558d324d9124219d05b9ebfc7b4e9db37800d))

- Restrict indexonly to logged-in assignee tickets ([d1c5359](https://github.com/aaact-aatia/request-management-tool/commit/d1c53599e0a7b25574d629eceb0487f9db5caef6))

- Allow Manager accounts to save team assignments ([65d4eda](https://github.com/aaact-aatia/request-management-tool/commit/65d4eda6f6df5db1079aaea1313d21c9d9e0c5f0))

- Menu visibility should respect current switched role in dev mode ([b42021c](https://github.com/aaact-aatia/request-management-tool/commit/b42021c843f16b5c103e761dab68d8f6a29a385a))

- Restrict Administration menu to Super Admin only in appmenu ([3f8329b](https://github.com/aaact-aatia/request-management-tool/commit/3f8329b2db3ba49073420492504106592626a33f))

- CSV import now skips empty lines when searching for header row ([722d787](https://github.com/aaact-aatia/request-management-tool/commit/722d7870fdccdcbc414cd762c00b1883cd13d050))

- Delete-comms.php redirect 404 - use viewrequest.php with lang param ([eedbfa6](https://github.com/aaact-aatia/request-management-tool/commit/eedbfa6a994872f127f07d82c30a2c6bd901b4cc))

- Use null coalescing operator to prevent undefined array key warnings ([0bd01ee](https://github.com/aaact-aatia/request-management-tool/commit/0bd01ee73fdc1b40a5f159b9bca4f942662d93dc))

- Resolve signin.php parse error and include helpers in header template ([48999b3](https://github.com/aaact-aatia/request-management-tool/commit/48999b3c7123258160824c4c76ab32a24a899d61))

- Remove dead environment-switching code causing fatal mysqli error ([1dd5816](https://github.com/aaact-aatia/request-management-tool/commit/1dd58168f860da8a8d7d3305083ee0fd57a9a1a2))

- Remove environment column from user insert statement ([fd977d6](https://github.com/aaact-aatia/request-management-tool/commit/fd977d6026ceb125a083d0f01c9f8a7082f4f0a2))

- Complete superuser test-mode permission behavior ([aa183e8](https://github.com/aaact-aatia/request-management-tool/commit/aa183e805dffe5bd14ec856ac431700d8b10eea7))

- Add configurable TLS handling for GC Notify in dev ([ddf6f05](https://github.com/aaact-aatia/request-management-tool/commit/ddf6f05985ba88a130fdc257d6467b6df22b9617))

- Stabilize localhost GC Notify test flow ([e156f0c](https://github.com/aaact-aatia/request-management-tool/commit/e156f0c76d32af5e4a2c16368b4c0e42b9cf302b))

- Avoid viewrequest result variable collision in header preview ([e18dba0](https://github.com/aaact-aatia/request-management-tool/commit/e18dba0976af270df1b6cbd2b699256ab5453bb9))

- Scope dev preview to submit flow only ([10bd289](https://github.com/aaact-aatia/request-management-tool/commit/10bd2890d44da2a2b1be763b95d3249bf90dbe5a))

- Restore admin access for seeded users ([79b1849](https://github.com/aaact-aatia/request-management-tool/commit/79b18499a0a9d795f9e93a863bb4b6181b585420))

- Make GC Notify connectivity test read app settings ([a338043](https://github.com/aaact-aatia/request-management-tool/commit/a3380433f379b5473668f22191887ffaf3ab1847))

- Harden director read-only behavior and align request card actions ([84bc553](https://github.com/aaact-aatia/request-management-tool/commit/84bc5533724e96846b951a081b248c053a52d4ab))

- Improve admin CSV import/export on empty and legacy tables ([c4df82a](https://github.com/aaact-aatia/request-management-tool/commit/c4df82a40f282b066ea1d755455d2429bb0ef706))

- Include docs in docker build context ([231057b](https://github.com/aaact-aatia/request-management-tool/commit/231057b3806f4efd9d84e84fa8de747e2e3c65ea))

- Allow Team Lead creation without team assignment ([11c6489](https://github.com/aaact-aatia/request-management-tool/commit/11c648920c0cdc14329a33f5c1d751146f6da983))

- Handle duplicate email on user creation ([bfa8cd3](https://github.com/aaact-aatia/request-management-tool/commit/bfa8cd3d26f3b52d6e6d97ded9bec0beaa21a27e))

- Prevent duplicate submissions and improve complete-state messaging ([56e9722](https://github.com/aaact-aatia/request-management-tool/commit/56e97224719ecfab25876975a09cefff924a4efe))

- Guard add-user double submit ([150f0ea](https://github.com/aaact-aatia/request-management-tool/commit/150f0ea4fdfd2a853a567a299c972d1ffd1fd562))

- Improve request edit success feedback ([323821b](https://github.com/aaact-aatia/request-management-tool/commit/323821b5144e64d859691e15decb63e2f430a56f))

- Stabilize section feedback, focus, and page layout ([5aa59a7](https://github.com/aaact-aatia/request-management-tool/commit/5aa59a77b37440acb5b3769911a6d64dc00d1c42))

- Align communications button count with modal ([ee2b399](https://github.com/aaact-aatia/request-management-tool/commit/ee2b39956fc441b7ffd995ce5a5011ce8db27e9a))

- Cast DATE columns to CHAR in NULLIF sort comparisons ([dd356f3](https://github.com/aaact-aatia/request-management-tool/commit/dd356f3d7c04e779f12197bc6ab67efcc18d70cc))

- Prevent admin modals from closing on outside click ([1bb83d6](https://github.com/aaact-aatia/request-management-tool/commit/1bb83d6c8cd7f9d20206bfb2baf8519085910df7))

- Remove stale SSC gate reference from guidance text hint ([5f635a2](https://github.com/aaact-aatia/request-management-tool/commit/5f635a259ec50ff694da67a37321462d79d2d34d))

- Handle users without team assignments ([418b366](https://github.com/aaact-aatia/request-management-tool/commit/418b366235cc6a00bd171da99f1735e427420088))

- Validate intake hierarchy at submission ([caa2b8c](https://github.com/aaact-aatia/request-management-tool/commit/caa2b8c22bcf42fe8b8aaa1afd2a054309fdbd6e))

- Make catalogue migration non-destructive ([0999a9b](https://github.com/aaact-aatia/request-management-tool/commit/0999a9be335a639e38fe94163157c9c9c5b2c60f))

- Constrain bootstrap catalogue hierarchy ([301c387](https://github.com/aaact-aatia/request-management-tool/commit/301c38783508381cf2af6d9919867b2240036c8a))

- Harden local file storage ([8952239](https://github.com/aaact-aatia/request-management-tool/commit/89522391a36d70429e94cc96dd34cefbaee8558d))

- Prepare public intake queries ([d018e9c](https://github.com/aaact-aatia/request-management-tool/commit/d018e9ce016e623767e8c55a895a7cbe761ea567))

- Secure priority recalculation ([a1c6037](https://github.com/aaact-aatia/request-management-tool/commit/a1c603779bdf51e4348d6cc788c2f0a1f5a06080))

- Complete organization directory management ([572462e](https://github.com/aaact-aatia/request-management-tool/commit/572462e2eb8d9648e49fcc598747598cd0e57e4b))

- Restore account password reset ([9c7c5cd](https://github.com/aaact-aatia/request-management-tool/commit/9c7c5cdff0ca990b12aff055a34bf62a868ff51d))

- Lock request ID during edits ([9dc8e2a](https://github.com/aaact-aatia/request-management-tool/commit/9dc8e2a00fdc78c3ef65980ce5a905e408af6848))

- Make request source optional ([ea964cb](https://github.com/aaact-aatia/request-management-tool/commit/ea964cba450b250fdc31a962388505954baf3f2b))

- Separate intake details from staff logs ([0c6c948](https://github.com/aaact-aatia/request-management-tool/commit/0c6c9488af51f4c1e6ca56a8de26c3891ecc115a))

- Remove intake information summary ([b8c4cee](https://github.com/aaact-aatia/request-management-tool/commit/b8c4cee86c4d3785a0c3a9a678c92dcc67e7cabb))

- Localize department selections ([cfb08d2](https://github.com/aaact-aatia/request-management-tool/commit/cfb08d2b90a98a816f91b01cffbad414f109ea64))

- Hide unavailable hierarchy fields ([b3151a4](https://github.com/aaact-aatia/request-management-tool/commit/b3151a4c951f73b69ac6c426a5c91d2598aa218a))

- Preserve subject types in local seeds ([ad81c59](https://github.com/aaact-aatia/request-management-tool/commit/ad81c5962a5ce67b9d7b3bf9934c635217458d27))

- Prevent browser autofill from suggesting saved department/agency values ([fc316a5](https://github.com/aaact-aatia/request-management-tool/commit/fc316a51049449bd6e117975d33e2de6a03a0a08))

- Repair client survey links ([34fdcad](https://github.com/aaact-aatia/request-management-tool/commit/34fdcad8046b00f81d4dc687a3df640fcc4f8536))

- Remove parent service SLA when subservices exist ([e0620eb](https://github.com/aaact-aatia/request-management-tool/commit/e0620eb2f63491f5b10a471e0f064ea44f61f586))

- Rename catalogue contact groups to teams ([8f2ad62](https://github.com/aaact-aatia/request-management-tool/commit/8f2ad6268706c20078d5aa65c9af8b379ed87258))

- Lock request lifecycle dates ([f8a9b17](https://github.com/aaact-aatia/request-management-tool/commit/f8a9b17609b72000be0e6a53012d6515c134326e))

- Log attachment authorization denials ([ec05606](https://github.com/aaact-aatia/request-management-tool/commit/ec056062a65ba7ab71da5e7683d1f518b86b1b61))

- Refresh attachment download script ([d30009a](https://github.com/aaact-aatia/request-management-tool/commit/d30009a8ba06937c8cdfb2175c4568cb412cc5e3))

- Hide generated dialog close footer ([b662120](https://github.com/aaact-aatia/request-management-tool/commit/b6621207336713de216c91f1fdf7b524e9e9a3d1))

- Keep language toggle on public host ([a3a6ab0](https://github.com/aaact-aatia/request-management-tool/commit/a3a6ab02580df267f5d49e6575180fbd7f8e2ee4))

- Correct French catalogue apostrophe ([56eb56e](https://github.com/aaact-aatia/request-management-tool/commit/56eb56e8a5a1bfd519e3658b0166a2d1a82b8310))

- Add unassigned request filter ([8d330da](https://github.com/aaact-aatia/request-management-tool/commit/8d330dac096784a61bb1d57a37badc2885892c24))

- Show request logs to authenticated users ([d1427e2](https://github.com/aaact-aatia/request-management-tool/commit/d1427e248f7337499cb80ae751b780002dfa9f9c))

- Hide resolved dates on active requests ([868d1cf](https://github.com/aaact-aatia/request-management-tool/commit/868d1cf25f5e55651d6faa8b3e09b4973fb7e593))

- Require department on intake form ([ffc5854](https://github.com/aaact-aatia/request-management-tool/commit/ffc585456a387014bddf469d35531fed7ce6f435))

- Modernize request cloning workflow ([d7eae72](https://github.com/aaact-aatia/request-management-tool/commit/d7eae72aa67005ebb43f512ca461acf87d99a506))

- Hide empty request service separator ([adc2e2a](https://github.com/aaact-aatia/request-management-tool/commit/adc2e2a9220d605358e84e432f9a83e7c8669c21))

- Show department status on request view ([4f08497](https://github.com/aaact-aatia/request-management-tool/commit/4f08497f769f72acac4bb4e91706266ead849493))


### Other

- Add or update the Azure App Service build and deployment workflow config ([b1b5bdc](https://github.com/aaact-aatia/request-management-tool/commit/b1b5bdc6318ea241656de3d7289c63d7cb32d43c))

- Add or update the Azure App Service build and deployment workflow config ([75678da](https://github.com/aaact-aatia/request-management-tool/commit/75678da95a41bd4999b0e015ae3ef06f24653899))

- Add demo request seed data for management presentation

Adds a variety of triage requests across all statuses, services, and
teams along with matching comm log notes and status history entries.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com> ([e15bbf0](https://github.com/aaact-aatia/request-management-tool/commit/e15bbf06497ced3b305b9d744e803f013296ac78))

- Revert "style: convert all checkbox lists to horizontal layout using list-inline"

This reverts commit 108e5947fc73c13cba089e08af03544ae3ad1e19. ([9632cbe](https://github.com/aaact-aatia/request-management-tool/commit/9632cbe4ba029c1a917ab6c4a912d73ad096b53a))

- Add defensive admin schema checks and harden bilingual lang attributes ([b26b72d](https://github.com/aaact-aatia/request-management-tool/commit/b26b72d68878ef8ccf8e7e3b2275a886ad0d9217))

- Refine request language display and resolved email workflow ([37ba1d3](https://github.com/aaact-aatia/request-management-tool/commit/37ba1d33e7f732c43599fd6189e2bbaf81ad93e0))

- Add GC Notify test email fallback for redirect mode ([e9d01d1](https://github.com/aaact-aatia/request-management-tool/commit/e9d01d1818019811158c83ef1f3a90ff3b5b6ea2))

- Improve notification behavior and service contact UX ([1f99ff2](https://github.com/aaact-aatia/request-management-tool/commit/1f99ff291cc0568d15e61dd1007de6c85162deea))

- Add dev notification recipient preview banner ([a36e7b3](https://github.com/aaact-aatia/request-management-tool/commit/a36e7b373250e073f323034fadc70d96e4f9bcc8))

- Use UI settings for notification runtime mode ([75fc450](https://github.com/aaact-aatia/request-management-tool/commit/75fc4508d2f5ee6eb8cdd6aef0281849a4a9fae2))

- Improve dev notification previews and role labeling ([61d4161](https://github.com/aaact-aatia/request-management-tool/commit/61d4161b421f33ea2ea4a4e3ed4e4508e27b7104))

- Reduce noisy session write notices on closed DB links ([d1172f9](https://github.com/aaact-aatia/request-management-tool/commit/d1172f9bcdad11b8cde8df8116d3c03f8db1fdaf))

- Move contact ownership to catalogue tier ([be975ec](https://github.com/aaact-aatia/request-management-tool/commit/be975ec0138f59a096d18273a76350ab10a222be))

- Fix closed-request pages when no results ([987ef30](https://github.com/aaact-aatia/request-management-tool/commit/987ef3068828cdb9d63b51fe32be4cc9a0245498))

- Harden closed requests page for prod session/query edge cases ([585965e](https://github.com/aaact-aatia/request-management-tool/commit/585965e22f2b5d753141e2bfa91f847c93a10680))

- - rely on repository variables for resource group and app names

- update warning to name the required repository variables ([33b5a40](https://github.com/aaact-aatia/request-management-tool/commit/33b5a40f40c54c80f79f19ab170690214426d827))

- Preserve configurable intake flow for future development ([e371f67](https://github.com/aaact-aatia/request-management-tool/commit/e371f67d3e19d016da12e6026e7710565e9dd291))

- Revert "Merge pull request #67 from aaact-aatia/request-catalogue"

This reverts commit 709ed3d7517df3a74649deb27fd1eb71e032d1db, reversing
changes made to 1f25d2de91afa814dd49160085b2f76a39fa88a5. ([9bfaf79](https://github.com/aaact-aatia/request-management-tool/commit/9bfaf79a81dd140ec16ea180394f2d91030c6d50))

- Compact request change log details ([2ef5c9a](https://github.com/aaact-aatia/request-management-tool/commit/2ef5c9a91c074ffd50e48f32bcc00c0b2aebfde5))

- Remove quick test request utility ([6ff0bc9](https://github.com/aaact-aatia/request-management-tool/commit/6ff0bc9ca02d2440459f9b7f3e2461f655e434d6))


### Removed

- Remove SAMS/OCMC request flow ([36aaef4](https://github.com/aaact-aatia/request-management-tool/commit/36aaef45541dc2bb93a245f569ab65c23d4db2fe))

- Remove NSD/Smart IT ticket field and data ([a672b69](https://github.com/aaact-aatia/request-management-tool/commit/a672b6920dab93dc530917004a4e3e73902d2e3f))

- Retire request source data ([4e8ff86](https://github.com/aaact-aatia/request-management-tool/commit/4e8ff860340dea6d92814076cb168153d0edcd52))


### Testing

- Expand dev requests with assignment and SLA scenarios ([eb5620e](https://github.com/aaact-aatia/request-management-tool/commit/eb5620e02856c56861816d37c4e8c4994aa1b153))

- Complete file upload coverage ([2013844](https://github.com/aaact-aatia/request-management-tool/commit/2013844583c575e0909c3d615a7ad411b15d8047))


