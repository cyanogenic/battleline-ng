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

