# STATUS

本文件用于持续记录每次较大改动的背景信息，减少后续对话里重复回忆上下文的成本。

建议后续每次迭代都按下面结构追加：

- 动机
- 改动前预期
- 实际改动后的效果
- 结果与预期对比
- 验证

---

## 2026-04-04 至 2026-04-05

### 1. 基础规则引擎

**动机**

- 在线版《战线》的核心风险不在页面，而在规则正确性。
- 如果先把规则写进 Controller / Model，后面加 API、实时同步、前端和战术牌会很容易失控。

**改动前预期**

- 将基础规则抽成纯 PHP 领域层。
- 先支持兵牌、阵型比较、夺旗、胜负和基础回合流转。
- 不依赖数据库和前端，便于单测锁规则。

**实际改动后的效果**

- 新增 `app/Domain/Game` 作为领域层。
- 已实现：
  - troop card / formation / game state / flag state / player state
  - 阵型判定与强弱比较
  - 夺旗判定
  - 连续 3 旗、任意 5 旗胜利条件
  - 出牌、claim、pass、finish turn
- 通过 Pest 单元测试覆盖基础规则。

**结果与预期对比**

- 与预期一致，且边界比最初更清晰。
- 当前仍未接入战术牌，这也是这阶段刻意保留的范围控制。

**验证**

- `php artisan test --compact tests/Unit/Domain/Game/BattleLineEngineTest.php`

---

### 2. 规则引擎接入 Laravel API

**动机**

- 规则层已经能独立运行，需要一个可持久化、可被前端调用的 Laravel 外壳。
- 目标是先跑通“创建对局 -> 查询局面 -> 执行动作”的主链路。

**改动前预期**

- 用一个简单的 `battle_line_games` 表保存完整游戏状态。
- 提供 JSON API，而不是立刻上复杂前端框架。
- 保证数据库里保存的是完整权威状态。

**实际改动后的效果**

- 新增 `BattleLineGame` 模型和 `battle_line_games` 表。
- 新增 API：
  - `POST /api/battle-line-games`
  - `GET /api/battle-line-games/{id}`
  - `POST /api/battle-line-games/{id}/actions`
- 用 `GameStateSerializer` 在领域对象和 JSON 持久化之间做转换。
- 用 `BattleLineGameController` 作为 Laravel 包装层调用规则引擎。

**结果与预期对比**

- 与预期一致。
- 当前持久化策略是“整局 state 存 JSON”，优点是简单稳定，缺点是后续做回放/审计时还需要引入 action log。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGameApiTest.php`

---

### 3. API 按玩家视角隐藏对手手牌

**动机**

- 在线卡牌游戏不能把完整状态直接返回给前端，否则对手手牌泄漏。
- 数据库存权威状态，但 API 必须返回“玩家投影视图”。

**改动前预期**

- 数据库仍保存完整手牌。
- API 根据 viewer 只返回自己的手牌，对手只返回 `hand_count`。

**实际改动后的效果**

- 新增 `GameStateViewProjector`。
- `create/show/action` 接口统一支持 viewer 视角。
- 前端和 API 消费到的是玩家投影，不是完整状态。

**结果与预期对比**

- 与预期一致。
- 额外收益是为后续扩展 spectator / replay / admin view 留出了统一投影入口。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGameApiTest.php`

---

### 4. API 返回结构整理为前端友好视图

**动机**

- 仅仅隐藏对手手牌还不够，前端如果还要自己判断“viewer / opponent / board / actions”，会反复写推导逻辑。
- 需要让响应结构接近 UI 直接消费的样子。

**改动前预期**

- 把返回结构改成：
  - `turn`
  - `viewer`
  - `opponent`
  - `board`
  - `available_actions`
- 让前端直接知道哪些旗可出牌、哪些旗可 claim。

**实际改动后的效果**

- `GameStateViewProjector` 直接输出前端友好结构。
- `BattleLineEngine` 增加了：
  - `claimableFlagIndexes`
  - `playableFlagIndexes`
  - `canPass`
  - `canFinishTurn`

**结果与预期对比**

- 与预期一致。
- 这一步显著降低了前端页面的复杂度，是目前 UI 能快速落地的关键。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGameApiTest.php`
- `php artisan test --compact tests/Unit/Domain/Game/BattleLineEngineTest.php`

---

### 5. 创建对局页与对局页

**动机**

- 仅有 API 不足以验证玩法链路，需要一个可直接试玩的网页界面。
- 目标是尽快跑通“建局 -> 进局 -> 查看双方信息 -> 出牌/claim/结束回合”。

**改动前预期**

- 用 Blade + Tailwind + 原生 JS 做最小可玩界面。
- 不引入额外前端框架。
- 复用现有 API，不复制规则。

**实际改动后的效果**

- 首页改成 Battle Line 开局页。
- 新增对局页，支持：
  - 查看 viewer / opponent / 旗位
  - 轮询刷新
  - 切换 viewer 视角
  - 选牌、出牌、claim、pass、finish turn
- 新增 Battle Line 专用视觉主题和 JS 交互脚本。

**结果与预期对比**

- 与预期一致。
- 前端栈保持很轻，便于快速迭代。
- 当前还没有动画、拖拽、操作日志和阵型预览，属于可继续增强的体验层功能。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `npm run build`

---

### 6. 对局页增强交互反馈

**动机**

- 第一版页面能玩，但缺少“我现在该做什么”的即时反馈。
- 用户容易在选牌、claim 时迷失，不知道哪些旗可操作、当前 phase 应该干什么。

**改动前预期**

- 为页面补上：
  - 当前行动建议
  - 选中卡牌后的部署预览
  - 每面旗更明确的状态标签
  - 成功/错误/普通信息提示

**实际改动后的效果**

- 新增 `Field Intel` 面板。
- 新增 `Selected Card` 面板。
- 每面旗新增更细的交互标签与说明：
  - `Claim Ready`
  - `Deploy`
  - `Secured`
  - `Lost`
  - `Formed`
  - `Unavailable`
- 动作请求前后会显示更明确的提示文案。

**结果与预期对比**

- 与预期一致。
- 这一层没有改动规则，只增强了状态可读性和操作引导，风险较低但体验收益很高。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact tests/Feature/BattleLineGameApiTest.php`
- `php artisan test --compact tests/Unit/Domain/Game/BattleLineEngineTest.php`
- `npm run build`

