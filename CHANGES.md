# CHANGES

本文件用于持续记录“具体改了什么”，尽量按功能块聚合，减少每次都要重新扫描代码树。

建议后续每次迭代都按下面结构追加：

- 本次目标
- 新增文件
- 修改文件
- 关键接口 / 路由 / 命令
- 备注

---

## 2026-04-04 至 2026-04-05

### 1. 基础规则引擎

**本次目标**

- 建立 Battle Line 基础规则的纯 PHP 领域层。

**新增文件**

- `app/Domain/Game/Enums/FormationRank.php`
- `app/Domain/Game/Enums/GamePhase.php`
- `app/Domain/Game/Enums/TroopColor.php`
- `app/Domain/Game/ValueObjects/TroopCard.php`
- `app/Domain/Game/ValueObjects/PlacedTroopCard.php`
- `app/Domain/Game/ValueObjects/Formation.php`
- `app/Domain/Game/Entities/FlagState.php`
- `app/Domain/Game/Entities/GameState.php`
- `app/Domain/Game/Entities/PlayerState.php`
- `app/Domain/Game/Evaluators/FormationEvaluator.php`
- `app/Domain/Game/Evaluators/FlagClaimEvaluator.php`
- `app/Domain/Game/Evaluators/VictoryEvaluator.php`
- `app/Domain/Game/Exceptions/InvalidGameAction.php`
- `app/Domain/Game/Services/BattleLineEngine.php`
- `tests/Unit/Domain/Game/BattleLineEngineTest.php`

**修改文件**

- 无

**关键接口 / 命令**

- 领域入口：`BattleLineEngine`
- 测试：`php artisan test --compact tests/Unit/Domain/Game/BattleLineEngineTest.php`

---

### 2. Laravel API 包装层

**本次目标**

- 把规则引擎接入 Laravel，提供持久化和 JSON API。

**新增文件**

- `app/Models/BattleLineGame.php`
- `database/migrations/2026_04_04_035059_create_battle_line_games_table.php`
- `app/Domain/Game/Support/GameStateSerializer.php`
- `app/Http/Controllers/Api/BattleLineGameController.php`
- `app/Http/Requests/StoreBattleLineGameRequest.php`
- `app/Http/Requests/ExecuteBattleLineActionRequest.php`
- `app/Http/Resources/BattleLineGameResource.php`
- `routes/api.php`
- `tests/Feature/BattleLineGameApiTest.php`

**修改文件**

- `bootstrap/app.php`

**关键接口 / 路由**

- `POST /api/battle-line-games`
- `GET /api/battle-line-games/{battleLineGame}`
- `POST /api/battle-line-games/{battleLineGame}/actions`

**备注**

- `battle_line_games.state` 采用 JSON 持久化完整状态。

---

### 3. 玩家视角投影

**本次目标**

- API 返回中隐藏对手手牌。

**新增文件**

- `app/Http/Requests/ShowBattleLineGameRequest.php`
- `app/Domain/Game/Support/GameStateViewProjector.php`

**修改文件**

- `app/Http/Requests/StoreBattleLineGameRequest.php`
- `app/Http/Resources/BattleLineGameResource.php`
- `app/Http/Controllers/Api/BattleLineGameController.php`
- `tests/Feature/BattleLineGameApiTest.php`

**关键接口 / 路由**

- `GET /api/battle-line-games/{battleLineGame}?viewer_player_id=...`
- `POST /api/battle-line-games` 新增 `viewer_player_id`

**备注**

- 数据库存完整状态，API 只返回投影视图。

---

### 4. 前端友好 API 结构

**本次目标**

- 把 API 响应整理成前端更直接可用的结构。

**新增文件**

- 无

**修改文件**

- `app/Domain/Game/Services/BattleLineEngine.php`
- `app/Domain/Game/Support/GameStateViewProjector.php`
- `tests/Feature/BattleLineGameApiTest.php`

**关键接口 / 返回结构**

- `state.turn`
- `state.viewer`
- `state.opponent`
- `state.board`
- `state.available_actions`

**备注**

- 新增规则辅助方法：
  - `claimableFlagIndexes`
  - `playableFlagIndexes`
  - `canPass`
  - `canFinishTurn`

---

### 5. Battle Line 页面层

**本次目标**

- 提供首页建局和对局页面。

**新增文件**

- `app/Http/Controllers/BattleLineGamePageController.php`
- `resources/views/components/layouts/battle-line.blade.php`
- `resources/views/battle-line/index.blade.php`
- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**修改文件**

- `routes/web.php`
- `resources/js/app.js`
- `resources/css/app.css`
- `composer.json`

**关键接口 / 路由**

- `GET /`
- `GET /battle-line-games/{battleLineGame}?viewer_player_id=...`

**备注**

- `composer.json` 的 `dev` 脚本里，`php artisan serve` 被改成了 `--host=0.0.0.0`。
- 前端采用 Blade + Tailwind + 原生 JS。

---

### 6. 对局页交互反馈增强

**本次目标**

- 增强对局页的状态反馈和交互提示。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键变更**

- 新增 `Field Intel`
- 新增 `Selected Card`
- 旗位状态标签与提示文案
- 动作成功 / 错误 / 普通提示

**备注**

- 这一轮没有新增规则能力，主要是 UI/UX 增强。

---

### 7. 真实用户认证与对局大厅

**本次目标**

- 让 Battle Line 从“单浏览器模拟双人”升级为“真实用户登录后创建 / 加入 / 参与对局”。

**新增文件**

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `app/Http/Requests/LoginRequest.php`
- `app/Http/Requests/RegisterUserRequest.php`
- `app/Http/Requests/JoinBattleLineGameRequest.php`
- `app/Policies/BattleLineGamePolicy.php`
- `database/migrations/2026_04_05_071057_add_user_relationships_to_battle_line_games_table.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `tests/Feature/Auth/AuthenticationTest.php`

**修改文件**

- `app/Models/BattleLineGame.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/BattleLineGamePageController.php`
- `app/Http/Controllers/Api/BattleLineGameController.php`
- `app/Http/Requests/StoreBattleLineGameRequest.php`
- `app/Http/Requests/ShowBattleLineGameRequest.php`
- `app/Http/Requests/ExecuteBattleLineActionRequest.php`
- `app/Domain/Game/Support/GameStateViewProjector.php`
- `app/Http/Resources/BattleLineGameResource.php`
- `routes/web.php`
- `routes/api.php`
- `resources/views/components/layouts/battle-line.blade.php`
- `resources/views/battle-line/index.blade.php`
- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGameApiTest.php`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 认证：
  - `GET /login`
  - `POST /login`
  - `GET /register`
  - `POST /register`
  - `POST /logout`
- 对局大厅与页面：
  - `GET /`
  - `POST /battle-line-games`
  - `POST /battle-line-games/{battleLineGame}/join`
  - `GET /battle-line-games/{battleLineGame}`
- 受认证保护的对局状态 / 动作接口：
  - `GET /battle-line-games/{battleLineGame}/state`
  - `POST /battle-line-games/{battleLineGame}/actions`
- 验证命令：
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- `battle_line_games` 现在通过用户外键绑定真实玩家，仍保留名字字段用于展示与胜者快照。
- 新建对局时先进入 `waiting_for_opponent`，第二名用户加入后才真正洗牌并初始化 `state`。
- 前端不再接收或切换任意 viewer，而是由后端根据当前登录用户映射成 `player_one` / `player_two` seat。

---

### 8. 单个玩家仅允许一个未关闭对局

**本次目标**

- 限制同一用户同时只能拥有一个未关闭对局，避免重复建局或多局并行。

**新增文件**

- 无

**修改文件**

- `app/Models/BattleLineGame.php`
- `app/Http/Requests/StoreBattleLineGameRequest.php`
- `app/Http/Requests/JoinBattleLineGameRequest.php`
- `app/Http/Controllers/BattleLineGamePageController.php`
- `resources/views/battle-line/index.blade.php`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 受影响流程：
  - `POST /battle-line-games`
  - `POST /battle-line-games/{battleLineGame}/join`
- 新增 open 相关查询能力：
  - `BattleLineGame::scopeOpen()`
  - `BattleLineGame::scopeOpenForUser()`
  - `BattleLineGame::isOpen()`
- 验证命令：
  - `php artisan test --compact`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 当前“未关闭对局”定义为 `status != game_over`，因此涵盖：
  - `waiting_for_opponent`
  - `playing_card`
  - `claiming_flags`
- 大厅页已经和后端规则同步：用户若已有未关闭对局，会看到提示并且无法继续创建或加入其他对局。

---

### 9. 对局页拖拽部署交互

**本次目标**

- 让战场页面中的手牌真正支持拖拽到旗位部署区，而不是只保留点击式操作。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 前端新增交互链路：
  - `dragstart`
  - `dragenter`
  - `dragover`
  - `dragleave`
  - `drop`
- 仍复用原有动作提交接口：
  - `POST /battle-line-games/{battleLineGame}/actions`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 这轮没有新增规则能力，只是把已有 `play_troop` 提交流程接到了拖拽交互上。
- 点击选牌再点击旗位的旧交互仍然保留，作为移动端和拖拽失败时的后备方案。

---

### 10. 扩大战线选区并加入出牌确认

**本次目标**

- 让整条战线都能作为部署目标，并在正式出牌前增加确认 / 取消步骤。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 前端部署流程从“直接提交”调整为：
  - 选牌
  - 选中合法旗位
  - `Confirm Deployment`
  - 提交 `POST /battle-line-games/{battleLineGame}/actions`
- 新增前端暂存状态：
  - `pendingDeployment`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 旗位卡片整体现在都可以响应点击和拖放，不再只依赖中间提示框的小范围命中区。
- 出牌确认仍属于前端交互层设计，后端动作接口没有变化。

---

### 11. 对局页右侧信息栏重排

**本次目标**

- 把 `Field Intel` 和 `Selected Card` 从左栏底部挪到主战区右侧，改善桌面端阅读和操作路径。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 页面布局从双栏调整为三栏桌面结构：
  - 左栏：回合 / 玩家 / Orders
  - 中栏：战场 / 手牌
  - 右栏：`Field Intel` / `Selected Card`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 这轮没有改动 JS 挂载点和动作接口，只调整了 Blade 结构与 Tailwind 布局类。
- 右栏在桌面端增加了 `sticky`，便于在对战中持续查看当前建议和已选卡牌。

---

### 12. 三栏同高与左右侧栏折叠

**本次目标**

- 让对局页三栏在桌面端更像统一工作台，并允许左右侧栏收起展开。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 页面布局调整：
  - 三栏容器增加桌面端同高约束
  - 左右栏改成可折叠的完整栏位外壳
- 前端新增侧栏状态逻辑：
  - `loadSidebarState()`
  - `persistSidebarState()`
  - `setupSidebarToggles()`
  - `applySidebarState()`
- 侧栏状态保存在浏览器 `localStorage` 的 `battle-line-sidebars`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 折叠不是简单隐藏内容，而是同步收窄对应 grid 列宽，让中间主战区得到更多空间。
- 现有 `data-turn`、`data-actions`、`data-feedback`、`data-selection` 挂载点保持不变，因此原有渲染逻辑可继续复用。

---

### 13. 对局页标题并入顶栏

**本次目标**

- 去掉对局页主体里的独立标题区，把关键对局信息并入最上方状态栏以节省高度。

**新增文件**

- 无

**修改文件**

- `resources/views/components/layouts/battle-line.blade.php`
- `resources/views/battle-line/show.blade.php`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- `battle-line` 布局新增可选命名插槽：
  - `topbar`
- 对局页现在通过 `x-slot:topbar` 注入：
  - 返回按钮
  - Battle 编号
  - 双方玩家名
  - 当前状态
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 这轮没有新增 JS 或接口变更，只调整了布局组件和对局页 Blade 结构。
- 其他页面未传入 `topbar` 插槽，因此不会出现额外中间栏内容。

---

### 14. 对局页通知改为浮层

**本次目标**

- 把对局页原本占据主战区高度的通知栏改成悬浮提示层。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- `data-game-alert` 从中间列普通块元素调整为固定定位浮层。
- `setAlert()` 现在会：
  - 保留成功 / 错误 / 普通样式切换
  - 按消息是否为空控制显隐
  - 维持浮层的阴影和模糊背景样式
- alert 元素查询从容器内查找改为全局查找，适配新的挂载位置。
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 这轮没有改变任何消息文案或触发时机，只改变了提示的呈现方式。
- 浮层现在位于顶栏下方，不再压缩战场首屏高度。

---

### 15. 浮层通知关闭按钮与全宽对局布局

**本次目标**

- 给浮层通知补上关闭按钮，并移除对局主体的最大宽度限制。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 浮层通知结构新增：
  - `data-game-alert-message`
  - `data-dismiss-alert`
- `setAlert()` 现在会更新内部消息节点，而不是直接覆盖整个 alert 容器文本。
- 对局页主容器移除：
  - `max-w-[1600px]`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 通知关闭后只会隐藏当前浮层，不会禁用后续新的提示。
- 三栏区域现在按页面可用宽度展开，仍保留原有左右内边距以避免贴边。

---

### 16. 侧栏头部副标题满宽

**本次目标**

- 让左右侧栏标题区的副标题不再被 `Hide` 按钮挤压，真正占满栏位宽度。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`

**关键接口 / 路由 / 命令**

- 左右侧栏 `data-sidebar-expanded-header` 从单层 `flex` 调整为两层结构：
  - 第一行：标题 + `Hide`
  - 第二行：副标题
- `Hide` 按钮新增 `shrink-0`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `npm run build`

**备注**

- 这是纯布局调整，没有改动 JS、接口或折叠状态逻辑。

---

### 17. 侧栏主标题独占一行

**本次目标**

- 让 `Seats & Orders` / `Field Intel` 这类侧栏 H2 真正独占整行，而不是继续与折叠按钮共享同一层布局。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`

**关键接口 / 路由 / 命令**

- 左右侧栏头部结构进一步拆成三层：
  - 眼眉 + `Hide`
  - H2
  - 副标题
- H2 新增显式：
  - `block`
  - `w-full`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `npm run build`

**备注**

- 这轮是对前一版侧栏头部的继续微调，目标是让标题视觉上真正铺满栏位宽度。

---

### 18. 当前行动方高亮到玩家卡片

**本次目标**

- 把 `Turn` 面板中的 `Active` 文本提示改成对对应玩家卡片的高亮。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- `Turn` 面板移除：
  - `Active: ...`
- 玩家卡片新增外层挂载点：
  - `data-player-panel-shell="viewer"`
  - `data-player-panel-shell="opponent"`
- 前端新增玩家高亮逻辑：
  - `syncPlayerPanelState()`
  - `syncPlayerPanelShell()`
- 玩家面板渲染新增 `isActive` 参数，用于显示 `Active Turn` 标记
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 这轮没有新增 API 字段，仍复用现有 `turn.is_viewer_active` 来判断高亮目标。

---

### 19. 预览取消时清理战线高亮

**本次目标**

- 让 `Selected Card` 里的 `Cancel` 真正撤销当前部署准备，而不是只清掉待确认旗位。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- `data-cancel-deployment` 点击后新增清理：
  - `selectedCardId`
  - `draggingCardId`
  - `hoverFlagIndex`
- 取消后新增手牌重渲染：
  - `renderHand(...)`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮没有修改后端动作，只修正了前端取消操作后的视觉和状态一致性。

---

### 20. 战线卡片改为三等分宽度

**本次目标**

- 让战场区域在任意主列宽度下，至少同时显示连续三条战线。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- 等待态旗位卡片宽度从：
  - `min-w-70`
  调整为：
  - `min-w-[calc((100%-2rem)/3)]`
- 对局态旗位卡片宽度同步采用相同策略
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `npm run build`

**备注**

- 这轮没有改变横向滚动模式，只改变了单个战线卡片的响应式宽度策略。

---

### 21. 战线卡片内部紧凑化

**本次目标**

- 提升“三条战线同屏”时的旗位内部可读性，让内容不会因为列宽变窄而过于拥挤。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- 旗位卡片调整：
  - 外层 `p-4` 改为 `p-3 sm:p-4`
  - 头部改成更适合窄列的 `flex-wrap`
  - 状态徽标 / `Claim` 按钮缩小
- 前后排区块调整：
  - `p-2.5 sm:p-3`
  - `min-h-24 sm:min-h-28`
- 部署提示按钮与空位占位文案缩小字号
- 已出牌小牌面改为：
  - `basis-0 flex-1`
  - 小尺寸高度 / 内边距 / 字号
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `npm run build`

**备注**

- 这轮没有调整交互逻辑，只是把战线卡片内部样式做成更适合三列同屏的版本。

---

### 22. 战线卡片宽度改成保底三条、可扩展更多

**本次目标**

- 让战线区不再永远锁死成三列，而是“至少三条完整可见，并尽可能多显示更多条战线”。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- 等待态与对局态旗位卡片宽度从：
  - `calc((100%-2rem)/3)`
  调整为：
  - `min(15rem, calc((100%-2rem)/3))`
- 具体挂载方式从：
  - `min-w-[...]`
  / 固定三等分语义
  调整为：
  - `basis-[min(15rem,calc((100%-2rem)/3))]`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `npm run build`

**备注**

- 现在主列窄时仍保底三条完整可见，主列宽时则会自动多露出第四条、第五条。

---

### 23. 待确认卡牌预览到目标战线

**本次目标**

- 在玩家已经选中目标旗位、但还未确认部署时，先把待确认卡牌显示在对应战线中。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- `renderBoard()` 新增待确认预览逻辑：
  - 找出 `pendingDeployment` 对应的手牌
  - 临时追加到目标旗位的 `viewer_cards`
  - 临时把该旗位牌数加一
- `renderPlacedCards()` 新增预览牌样式支持：
  - `isPendingPreview`
  - 金色高亮边框 / ring
  - `Preview` 标签
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 预览牌完全由前端状态驱动，不会写入后端游戏状态。
- 取消操作后，因为 `pendingDeployment` 会被清理，这张预览牌也会随之消失。

---

### 24. 待确认阶段只高亮目标战线

**本次目标**

- 修正待确认部署时的高亮范围，避免其它战线继续同步显示为当前卡牌的可落点。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- `renderBoard()` 的部署高亮判断改为统一调用 `canDropOnFlag()`
- `canDropOnFlag()` 新增待确认约束：
  - 若存在 `pendingDeployment`
  - 且当前旗位不是其目标旗位
  - 则直接返回不可高亮 / 不可投放状态
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮只收敛前端待确认态的视觉反馈，不改变真实的出牌规则。

---

### 25. Seats 移到右栏，部署确认改回 Orders

**本次目标**

- 拆分原先混在一起的 `Seats & Orders`，并移除右栏 `Deployment preview` 面板。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 左栏头部从：
  - `Seats & Orders`
  调整为：
  - `Orders`
- `data-player-panel-shell="viewer"` / `data-player-panel-shell="opponent"` 从左栏移到右栏
- 右栏头部改为：
  - `Seat Rail`
  - `Seats`
- 移除右栏：
  - `Selected Card`
  - `Deployment preview`
  - `data-selection`
- `renderActions()` 现在会在待确认状态下改写按钮区：
  - 第一颗按钮变成 `Cancel Deployment`
  - 第二颗按钮在原 `Finish Turn` 位置显示 `Confirm Deployment`
- 新增前端辅助方法：
  - `cancelPendingDeployment()`
- 文案同步改为从 `Orders` 面板完成确认 / 取消
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`
  - `vendor/bin/pint --dirty --format agent`

**备注**

- 这轮没有修改后端规则，只是重排页面信息结构并收口部署确认入口。

---

### 26. 顶栏 Battle 按钮触发 Field Intel 弹窗

**本次目标**

- 移除顶栏冗余控件，并把 `Field Intel` 改成点击 `Battle #id` 后弹出的浮窗。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 顶栏移除：
  - `Back`
  - 状态徽标（如 `playing card`）
- 顶栏新增：
  - `data-open-feedback-modal`
- 页面新增弹窗结构：
  - `data-feedback-modal`
  - `data-feedback-modal-panel`
  - `data-close-feedback-modal`
- `data-feedback` 从右栏常驻卡片迁移到弹窗面板
- 前端新增弹窗控制方法：
  - `setupFeedbackModal()`
  - `setFeedbackModalVisibility()`
- 弹窗支持：
  - 点击 `Battle #id` 打开
  - 点击遮罩关闭
  - 点击 `Close` 关闭
  - `Escape` 关闭
- 验证命令：
  - `vendor/bin/pint --dirty --format agent`
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮没有改动游戏规则或动作接口，只调整了情报区的展示方式。

---

### 27. 拖拽目标战线只保留单一高亮色

**本次目标**

- 修正拖拽卡牌时同一战线出现两种高亮色叠加的问题。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- `renderBoard()` 新增聚焦态样式分流：
  - 待确认目标旗位初始渲染改为直接使用金色样式
  - 不再和默认可投放态共用同一套琥珀色类
- `syncBoardState()` 新增两类显式判断：
  - `usesDefaultDropHighlight`
  - `usesFocusedDropHighlight`
- `data-play-flag-hint` 的类切换改为互斥：
  - 默认可投放态使用琥珀色
  - 当前悬停 / 待确认态使用金色
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮只调整拖拽反馈样式，不改变可部署判定和出牌确认流程。

---

### 28. 全局状态栏合并账号入口并去掉缩放高亮

**本次目标**

- 提升对局页的沉浸感：精简全局状态栏、合并账号入口、禁用右键，并移除手牌 / 战线高亮时的放大效果。

**新增文件**

- 无

**修改文件**

- `resources/views/components/layouts/battle-line.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 状态栏左侧从：
  - `Battle Line` 标签 + `Command Hall`
  调整为：
  - 单独的 `Command Hall` 按钮
- 状态栏右侧登录态改为 `details` 下拉：
  - 摘要按钮显示 `Commander {name}`
  - 下拉面板内提供 `Sign out`
- 对局页新增沉浸式限制：
  - `setupImmersiveInteractions()`
  - 禁止 `contextmenu`
- 手牌选中态移除：
  - `scale-[1.02]`
  - `-translate-y-1`
- 战线高亮态移除：
  - `scale-[1.01]`
  - `-translate-y-1`
- 选中和高亮现在主要依赖：
  - `border`
  - `ring`
  - `shadow`
- 验证命令：
  - `vendor/bin/pint --dirty --format agent`
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮不改游戏逻辑，只调整壳层导航和前端交互反馈。

---

### 29. 账号下拉层级与动画补强

**本次目标**

- 修复账号下拉被战线遮挡的问题，并继续打磨顶部壳层控件的开合体验。

**新增文件**

- 无

**修改文件**

- `resources/views/components/layouts/battle-line.blade.php`
- `resources/views/battle-line/show.blade.php`
- `resources/js/app.js`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- 顶栏主容器新增更高层级与显式溢出显示：
  - `relative z-40`
  - `overflow-visible`
- 账号菜单新增：
  - `data-account-menu`
  - 打开状态更高的层级
  - `opacity / translate / scale` 过渡
- `resources/js/app.js` 新增账号菜单收口逻辑：
  - 点击外部关闭
  - `Escape` 关闭
- `Field Intel` 浮窗补充更平滑的过渡：
  - overlay `duration-300 ease-out`
  - panel `translate-y / scale / opacity`
- `setFeedbackModalVisibility()` 新增对 panel 的：
  - `scale-[0.98] / scale-100`
  - `opacity-0 / opacity-100`
- 验证命令：
  - `vendor/bin/pint --dirty --format agent`
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮没有变更游戏数据或页面结构主分区，重点是修正 stacking context 与弹出层观感。

---

### 30. 战线与手牌滚动带增加缓冲内边距

**本次目标**

- 解决战线金色描边显示不全的问题，并预防同类裁切出现在手牌选中态上。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`

**关键接口 / 路由 / 命令**

- `data-board` 容器从：
  - `pb-2`
  调整为：
  - `px-1 py-1 pb-3`
- `data-hand` 容器从：
  - `pb-2`
  调整为：
  - `px-1 py-1 pb-3`
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮不改交互逻辑，只给横向滚动带补缓冲空间，让 ring 不会紧贴容器边缘。

---

### 31. 战线卡片高亮改为 inset ring

**本次目标**

- 把战线描边从外扩 ring 改成 inset ring，进一步避免高亮被裁切。

**新增文件**

- 无

**修改文件**

- `resources/js/battle-line-ui.js`

**关键接口 / 路由 / 命令**

- 战线卡片外层 `article[data-flag-target]` 新增：
  - `ring-inset`
- 现有战线 ring 逻辑保持不变：
  - `ring-2`
  - `ring-4`
  - `ring-war-gold/*`
  - `ring-war-ember/*`
  但绘制方向改为卡片内嵌
- 验证命令：
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮没有再改滚动带留白和高亮判定，只是把战线 ring 的绘制方式改得更稳。

---

### 32. Field Intel 增加九条战线总览

**本次目标**

- 让 `Field Intel` 不只提供当前步骤提示，还能给出 9 条战线的整体态势总览。

**新增文件**

- 无

**修改文件**

- `resources/views/battle-line/show.blade.php`
- `resources/js/battle-line-ui.js`
- `tests/Feature/BattleLineGamePageTest.php`

**关键接口 / 路由 / 命令**

- `Field Intel` 浮窗宽度从：
  - `max-w-3xl`
  调整为：
  - `max-w-6xl`
- 浮窗说明文案改为：
  - `Live tactical read for all nine lines.`
- `renderFeedback()` 重构为双区布局：
  - 左侧：当前反馈卡 + checklist + 全局摘要指标
  - 右侧：`Nine-Flag Overview`
- 新增前端数据组装方法：
  - `deriveBattlefieldIntel()`
  - `summarizeFlagForIntel()`
- 九线总览卡会基于当前局面输出：
  - `Secured`
  - `Lost`
  - `Queued`
  - `Claim Ready`
  - `Deployable`
  - `Formed`
  - `Pressured`
  - `Open`
- 验证命令：
  - `vendor/bin/pint --dirty --format agent`
  - `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
  - `php artisan test --compact`
  - `npm run build`

**备注**

- 这轮没有改变规则和动作接口，只重构了 `Field Intel` 的展示层和态势汇总逻辑。
