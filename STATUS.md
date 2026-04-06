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

---

### 7. 真实用户认证与可加入对局流程

**动机**

- 现有页面虽然能试玩，但本质上仍是同一浏览器模拟两个名字切换视角，不是真正的在线对战。
- 只靠前端传 `viewer_player_id` / `player_id` 也不安全，任何客户端都能伪造自己是任意玩家。

**改动前预期**

- 接入 Laravel 原生登录、注册、退出。
- 让 `battle_line_games` 与 `users` 建立真实关联。
- 把流程改成“用户创建待加入对局 -> 另一名登录用户加入 -> 后端按当前登录用户识别座位并返回玩家视角”。

**实际改动后的效果**

- 新增注册、登录、退出页面与会话认证流程。
- `battle_line_games` 新增玩家 / 胜者用户外键：
  - `player_one_user_id`
  - `player_two_user_id`
  - `winner_user_id`
- 首页改成真实大厅：
  - 未登录用户看到登录 / 注册入口
  - 已登录用户可创建待加入对局
  - 已登录用户可加入其他人创建的待加入对局
  - 已登录用户可查看自己的对局列表
- 对局页不再支持手动切换 viewer，而是根据当前登录用户自动识别 `player_one` / `player_two` 座位。
- 对局 JSON 接口从匿名 API 语义切换为受 `auth` 保护的 Web 路由，并通过 policy / request authorize 限制只有参与者可查看或操作。
- `GameStateViewProjector` 现在会把 seat id 映射成真实玩家名，前端显示的是玩家名而不是内部 seat key。

**结果与预期对比**

- 与预期一致，而且比最初目标多补了一层权限收口：
  - 页面访问受限
  - 状态查询受限
  - 动作提交受限
- 当前已具备“两个真实账号登录后完整对战”的基础能力。
- 仍未实现好友邀请、公开观战、匹配队列和断线重连，这些属于后续大厅/联网体验增强。

**验证**

- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 8. 单个玩家仅允许一个未关闭对局

**动机**

- 已经接入真实用户后，如果同一用户还能同时创建或加入多个未结束对局，大厅会很快失去一致性。
- 从使用体验上看，Battle Line 当前更适合“一个玩家同时专注一局”，否则等待中和进行中的对局都会变得混乱。

**改动前预期**

- 把“未关闭对局”定义统一到模型层。
- 当玩家已有等待中或进行中的对局时，阻止其再创建新对局。
- 同时保持大厅 UI 与后端校验一致，避免前端可点、后端又拒绝的割裂体验。

**实际改动后的效果**

- 在 `BattleLineGame` 中新增 open 相关查询能力：
  - `scopeOpen`
  - `scopeOpenForUser`
  - `isOpen`
- 建局请求和加入请求都增加了额外校验：
  - 已有未关闭对局时，不能再创建新对局
  - 已有未关闭对局时，也不能再加入别人的等待对局
- `BattleLineGamePageController` 额外做了后端兜底检查，避免并发请求绕过表单校验。
- 大厅页会明确提示当前用户已经有未关闭对局，并禁用“创建对局 / 加入对局”按钮。

**结果与预期对比**

- 与预期一致。
- 这轮不仅限制了创建，还顺手把加入流程一并收口，和“单个玩家同时只有一个未关闭对局”的规则保持一致。
- 当前“关闭”仍等价于 `game_over`；如果以后引入显式认输、取消对局、归档状态，需要把 open 判定一起扩展。

**验证**

- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`

---

### 9. 对局页补上真实拖拽部署

**动机**

- 对局页虽然已有“选牌后点击旗位”的备用流程，但界面本身一直像是支持拖拽，实际却没有真正的 drag/drop 链路。
- 这会让用户预期和行为脱节，尤其在卡牌游戏场景里，拖拽是更自然的主要操作。

**改动前预期**

- 让手牌真正可拖拽。
- 让可部署旗位真正可接收 drop。
- 保留点击部署作为兼容和兜底方案。

**实际改动后的效果**

- 对局页手牌现在会在可出牌阶段变成真正可拖拽元素。
- 旗位部署区新增了 `dragenter / dragover / dragleave / drop` 处理逻辑。
- 拖拽开始时会同步：
  - 选中当前卡牌
  - 更新 `Selected Card`
  - 更新 `Field Intel`
  - 高亮可放置旗位
- 拖拽经过合法旗位时会出现更明显的高亮反馈。
- 放手后会直接复用既有 `play_troop` 动作提交逻辑。
- 点击卡牌后再点击旗位的旧交互仍然保留，因此拖拽失败或不习惯拖拽时仍可继续操作。

**结果与预期对比**

- 与预期一致。
- 这轮没有改规则和后端接口，属于纯交互层增强，风险较低但体验提升很直接。
- 当前拖拽主要针对桌面浏览器；移动端依旧可以使用点击选牌再点击旗位的方式完成部署。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 10. 扩大战线选区并增加出牌确认

**动机**

- 拖拽虽然已经接通，但实际可放置区域主要还是中间那块提示框，命中范围太小，操作上容易“像是拖到了，其实没放上去”。
- 出牌又是即时提交，玩家一旦误点或误拖，就没有撤回空间，容错性不够。

**改动前预期**

- 把整条合法战线都变成可点击 / 可拖放的部署目标，而不是只依赖中间的小框。
- 出牌改成“两步式”：先选中战线，后确认提交。
- 保留原有点击选牌的节奏，不额外增加规则层复杂度。

**实际改动后的效果**

- 每个旗位卡片整体都能作为部署目标：
  - 选中手牌后，可以直接点击整条高亮战线
  - 拖拽时，也可以把卡牌放到整条高亮战线上
- 新增 `pendingDeployment` 前端状态，出牌流程改为：
  - 选牌
  - 选战线
  - 在 `Selected Card` 面板确认或取消
- 已暂存的旗位会显示更明确的排队提示，并允许用户取消后重新选择。
- 点到非法旗位时会给出错误提示，不再是“无反馈地什么都没发生”。

**结果与预期对比**

- 与预期一致。
- 这轮把“目标太小”和“误触无撤回”两个问题一并解决，而且没有改动后端规则接口。
- 当前确认仍然是前端交互层确认，不是后端事务性撤回；一旦点击确认，依旧按正式出牌处理。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 11. 对局页信息面板改为右侧栏

**动机**

- `Field Intel` 和 `Selected Card` 放在左栏底部时，阅读路径会被拉得很长。
- 玩家在操作战场和手牌时，更自然的视线落点其实是主战区右侧，而不是左下角。

**改动前预期**

- 保留左侧的回合、双方信息和操作按钮。
- 把 `Field Intel` 与 `Selected Card` 独立成右侧栏。
- 不改现有 JS 数据挂载点，尽量只调整布局层。

**实际改动后的效果**

- 对局页由双栏改成三栏桌面布局：
  - 左侧：Turn / Commander / Opponent / Orders
  - 中间：提示、战场、手牌
  - 右侧：`Field Intel` / `Selected Card`
- 右侧信息栏增加了 `sticky` 定位，在桌面端更容易边看边操作。
- 现有 `data-feedback` 和 `data-selection` 挂载点保持不变，因此前端交互逻辑无需跟着调整。

**结果与预期对比**

- 与预期一致。
- 这轮主要提升了信息读取路径，没有改变玩法和接口。
- 小屏幕下仍会按文档流向下堆叠，避免为移动端强上过窄三栏。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`

---

### 12. 三栏同高并支持左右侧栏折叠

**动机**

- 右移后的三栏布局虽然信息路径更顺了，但左右两栏仍像是独立卡片堆，不够像一个完整的对局工作台。
- 当玩家想专注看战场时，左右栏一直占宽，尤其在中等屏幕桌面上会压缩中间主战区。

**改动前预期**

- 让三栏在桌面端尽量形成一致的纵向高度。
- 让左栏和右栏都能折叠，给中间战场让出更多横向空间。
- 保留现有 `data-*` 挂载点，避免影响对局状态渲染。

**实际改动后的效果**

- 对局页三栏容器新增桌面端最小高度和 stretch 布局，左右栏改成完整的同高外壳。
- 左栏与右栏都新增了折叠按钮：
  - 左栏收起后保留 `Command` 竖排展开入口
  - 右栏收起后保留 `Intel` 竖排展开入口
- 侧栏开合状态会保存在浏览器本地，下次刷新仍会保持上次布局。
- 折叠时中间战场列会实际变宽，而不只是把内容隐藏掉。

**结果与预期对比**

- 与预期一致。
- 这轮仍然只改页面结构和前端交互层，没有动规则或接口。
- 当前折叠主要面向桌面端宽屏布局；小屏幕下依然按自然文档流上下排列。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 13. 对局页标题并入顶栏以节省垂直空间

**动机**

- 对局页在三栏布局之外还保留了一整段独立 `<header>`，会继续挤占第一屏高度。
- 这些信息本身已经很接近“全局状态信息”，放进最顶上的状态栏更顺，也更省空间。

**改动前预期**

- 去掉对局页主体里的独立标题区。
- 把返回入口、Battle 编号、对战双方和状态并入顶栏。
- 不影响其他使用同一布局组件的页面。

**实际改动后的效果**

- `battle-line` 布局组件新增可选顶栏插槽，允许页面把额外状态内容注入到最上方导航条中。
- 对局页移除了原本的 `<header>`，并把以下信息迁入顶栏中部：
  - 返回按钮
  - `Battle #id`
  - 对战双方
  - 当前状态
- 主体 `main` 的上下内边距同步缩小，让战场区域更早进入首屏。

**结果与预期对比**

- 与预期一致。
- 这轮主要是信息层级和纵向空间优化，没有改动对局逻辑或前端状态管理。
- 其他页面由于没有传入顶栏插槽，仍保持原来的导航结构不变。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`

---

### 14. 通知栏改为悬浮提示层

**动机**

- 对局页已经把标题并入顶栏，但中间主战区上方仍有一整条通知栏，会继续占用首屏高度。
- 这类信息本质上更像“状态 toast”，做成浮层会更贴合它的用途。

**改动前预期**

- 保留现有成功 / 错误 / 普通信息提示逻辑。
- 把通知栏从文档流中移出，不再占用主战区高度。
- 尽量少改现有对局脚本，只调整挂载位置和样式。

**实际改动后的效果**

- `data-game-alert` 改成固定在页面上方的悬浮提示层，位于顶栏下方。
- 中间主战区不再为了通知栏预留一整行空间。
- `setAlert()` 现在会根据消息是否为空自动切换浮层的显隐状态，而不是始终保留一块空白区域。
- JS 的 alert 挂载查找改成全局查询，避免浮层脱离战场容器后失去引用。

**结果与预期对比**

- 与预期一致。
- 这轮没有改动任何游戏规则或动作接口，只是把提示从“占位栏”改成了“悬浮层”。
- 当前提示会停留到下一次刷新或下一条消息到来，后续如果需要可以再加自动淡出。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 15. 浮层通知支持关闭，并让三栏铺满宽度

**动机**

- 浮层通知已经不占文档流高度，但还缺少手动关闭能力，玩家只能等待下一条消息把它覆盖掉。
- 对局主体虽然已经是三栏工作台，但主容器还保留了最大宽度限制，无法真正占满整页横向空间。

**改动前预期**

- 为浮层通知增加明确的关闭按钮。
- 保持现有提示逻辑不变，只补上“手动收起当前消息”能力。
- 去掉三栏主容器的最大宽度限制，让战场在宽屏上获得完整可用空间。

**实际改动后的效果**

- 浮层通知新增 `Close` 按钮，点击后会立即隐藏当前通知。
- `setAlert()` 改成只更新内部消息文本，不会破坏通知浮层里的按钮结构。
- 对局页主 `main` 容器移除了 `max-w-[1600px]`，三栏区域现在会跟随页面可用宽度展开。

**结果与预期对比**

- 与预期一致。
- 这轮属于纯页面结构和交互补强，没有改变任何对局规则或动作触发条件。
- 当前关闭只是隐藏当前消息；下一次有新提示时，浮层仍会正常重新出现。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 16. 侧栏标题区改成满宽副标题

**动机**

- 左右侧栏头部把标题、副标题和 `Hide` 按钮放在同一行流里时，副标题会被按钮明显挤窄。
- 这会让像 `Seats & Orders` 这样的栏目看起来没有真正用满整列宽度，阅读节奏也不顺。

**改动前预期**

- 保留现有标题和折叠按钮位置关系。
- 让副标题改为独占下一行，使用整列宽度。
- 左右侧栏的头部结构保持一致。

**实际改动后的效果**

- 左右侧栏头部都改成了两层结构：
  - 第一层：标题 + `Hide`
  - 第二层：满宽副标题
- `Hide` 按钮增加了 `shrink-0`，避免反向压缩标题区。
- `Seats & Orders` 与 `Field Intel` 的副标题现在都会按整列宽度展开。

**结果与预期对比**

- 与预期一致。
- 这轮只是微调侧栏头部的排版结构，没有改变折叠行为或对局逻辑。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `npm run build`

---

### 17. 侧栏 H2 改为独占整行

**动机**

- 上一轮虽然让副标题脱离了 `Hide` 按钮，但 `Seats & Orders` 这类 H2 仍然和按钮同处第一层结构里，视觉上依旧像没有真正占满整列。
- 这种问题本质上不是文字宽度不够，而是标题还在和按钮竞争同一行的布局上下文。

**改动前预期**

- 让侧栏眼眉、折叠按钮、主标题、副标题分别落在更清晰的层级里。
- 让 H2 本身单独占一整行。
- 左右侧栏保持对称结构。

**实际改动后的效果**

- 左右侧栏头部都改成：
  - 第一行：眼眉 + `Hide`
  - 第二行：H2
  - 第三行：副标题
- `Seats & Orders` 和 `Field Intel` 现在都是真正独占一整行的标题，不再受按钮同行挤压。

**结果与预期对比**

- 与预期一致。
- 这是在上一轮头部重排基础上的继续收口，属于纯布局精修，没有改变交互。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `npm run build`

---

### 18. 当前行动方改为高亮玩家卡片

**动机**

- `Turn` 面板里的 `Active: ...` 只是文字提示，用户视线还要再跳去玩家栏位做一次匹配。
- 对局页既然已经有独立的 `Commander / Opponent` 卡片，更自然的做法是直接把当前行动方高亮出来。

**改动前预期**

- 去掉 `Turn` 面板里的 `Active` 文本。
- 直接高亮当前行动方对应的玩家卡片。
- 保持当前回合信息仍能在 `Turn` 面板中看到 phase / deck / play order。

**实际改动后的效果**

- `Turn` 面板移除了 `Active: 玩家名` 这一行。
- `Commander` / `Opponent` 卡片会根据当前回合自动切换高亮：
  - 当前行动方会显示金色高亮外框与背景
  - 卡片标题旁会出现 `Active Turn` 标记
- 这一高亮完全由前端根据 `turn.is_viewer_active` 推导，不需要新增后端接口字段。

**结果与预期对比**

- 与预期一致。
- 这轮把“当前行动方”的信息从抽象文字，改成了更直接的界面焦点提示。
- 当前如果对局已结束，不会再保留行动方高亮，避免和胜负信息混淆。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 19. 取消预览时同步撤销战线高亮

**动机**

- 预览区的 `Cancel` 之前只会清除待确认的旗位，但不会取消当前选中的卡牌。
- 结果就是预览虽然消失了，战线上的可部署高亮却还留着，用户会觉得“取消没有完全生效”。

**改动前预期**

- 点击 `Cancel` 后，应把这次部署准备整体撤销。
- 不仅取消待确认旗位，也同步取消卡牌选中和战线高亮。
- 保持后端状态不变，只修正前端交互一致性。

**实际改动后的效果**

- `Cancel` 现在会同时清空：
  - `pendingDeployment`
  - `selectedCardId`
  - `draggingCardId`
  - `hoverFlagIndex`
- 取消后会重新渲染手牌、战场、预览和反馈，因此战线高亮会一并消失。
- 提示文案也调整成更准确的“需要重新选牌后再继续”。

**结果与预期对比**

- 与预期一致。
- 这轮只是修正前端状态联动，没有改变任何规则或动作接口。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 20. 战线区保证至少同时可见三条旗位

**动机**

- 战线卡片之前使用固定最小宽度，主列一旦被侧栏压缩，就只能看到一到两条旗位。
- 这会让战场信息太碎，用户频繁横向滚动才能建立整体局势感。

**改动前预期**

- 不改变九条战线横向滚动的整体模式。
- 让单个旗位宽度跟随战场容器动态计算。
- 在任意主列宽度下，都至少能同时看到连续三条战线。

**实际改动后的效果**

- 等待态和对局态下的旗位卡片宽度都从固定最小宽度改成了容器三等分：
  - `calc((100% - 2rem) / 3)`
- 在保留两段横向 gap 的前提下，战场视口内现在至少会露出连续三条旗位。
- 横向滚动仍然保留，只是单个旗位宽度改成了更偏“看全局”的响应式策略。

**结果与预期对比**

- 与预期一致。
- 这轮没有调整规则和交互，只优化了战场信息密度。
- 由于每条旗位会随主列变窄而缩小，极窄宽度下信息会更紧凑，但仍能维持“三条连续旗位可见”这个目标。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `npm run build`

---

### 21. 三列战线下压缩旗位内部排版

**动机**

- 虽然战线区已经能稳定同屏看到三条旗位，但旗位内部最初是按更宽卡片设计的，三列并排时会显得偏挤。
- 尤其是状态徽标、claim 按钮、前后排区域标题和已出牌小牌面，在窄宽度下需要更紧凑的节奏。

**改动前预期**

- 不改变三列同屏和横向滚动的整体策略。
- 只压缩旗位卡片内部的字号、间距和局部卡片尺寸。
- 让三列同屏时依旧保持清晰可读。

**实际改动后的效果**

- 等待态和对局态旗位卡片都改成了更紧凑的内边距。
- 旗位头部改成更适合窄卡片的折行与对齐方式：
  - 标题区域允许更自然换行
  - 状态徽标与 `Claim` 按钮尺寸更紧凑
- 前后排区块、部署提示区、空位占位文案的字号都做了小一档的压缩。
- 已出牌的小牌面改成自适应 `flex-1` 宽度，在三列布局下更容易完整放进一条战线里。

**结果与预期对比**

- 与预期一致。
- 这轮继续聚焦在三列同屏后的可读性，而不是再去改外层布局。
- 在更宽屏幕上，大部分元素仍会回到较舒展的 `sm` 尺寸，不会一直保持过紧样式。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `npm run build`

---

### 22. 战线宽度改成“三条保底，更多优先”

**动机**

- 之前把战线卡片直接做成容器三等分，虽然保证了至少三条同屏，但也把上限锁死成了“永远只显示三条”。
- 用户真正需要的是：
  - 再怎么缩放、侧栏怎么开合，也至少完整显示三条
  - 一旦主列更宽，就尽可能多显示第四条、第五条

**改动前预期**

- 保留“三条保底”的约束。
- 给单条战线增加一个舒适宽度上限。
- 当主列宽于这个上限时，让更多战线自然进入视口。

**实际改动后的效果**

- 等待态和对局态旗位卡片宽度都从“严格三等分”调整为：
  - `min(15rem, (容器宽度 - 两段 gap) / 3)`
- 这意味着：
  - 主列较窄时，卡片会缩到三等分宽度，保证至少三条完整显示
  - 主列较宽时，卡片会停在更舒适的 `15rem` 宽度，从而让第四条、第五条有机会一并显示

**结果与预期对比**

- 与预期一致。
- 这一轮把“至少三条”和“尽量更多条”这两个目标统一到了同一套宽度策略里。
- 当前体验上会更接近真正的横向棋盘/战线视图，而不是死板的三列卡片。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `npm run build`

---

### 23. 待确认卡牌先预览到战线上

**动机**

- 之前玩家在 `Preview` 里看到的是待确认卡牌的摘要，但战线本身还没有直观显示“这张牌会落在哪”。
- 这会让确认前的空间感不足，尤其在多条战线并排时，用户仍需要额外在脑中对照旗位。

**改动前预期**

- 当玩家已经选中旗位、进入待确认状态时，把那张卡临时显示在对应战线的 `Your Line` 中。
- 预览牌要和正式已出牌区分开，有明确高亮。
- 当用户取消操作时，这张预览牌也要随其它高亮一起消失。

**实际改动后的效果**

- 待确认状态下，目标旗位会在 `Your Line` 中插入一张临时预览牌。
- 这张预览牌会：
  - 使用金色边框 / ring 高亮
  - 在底部标记为 `Preview`
  - 让该旗位的牌数显示临时加一
- 因为它完全依赖 `pendingDeployment` 状态渲染，所以用户点击 `Cancel` 后，它会和旗位高亮、选牌状态一起被清除。

**结果与预期对比**

- 与预期一致。
- 这轮把确认前的部署提示从“面板提示”扩展成了“战线内预览”，更接近真实出牌手感。
- 当前预览牌只在已选中目标旗位后显示，不会在所有可部署旗位上同时铺开，避免界面噪音过高。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 24. 待确认状态只高亮目标战线

**动机**

- 在上一轮加入战线内预览后，待确认状态已经有了明确的目标旗位。
- 但界面仍会把其它可部署旗位一起亮出来，造成“明明已经选定了，为什么别的线还像可落点一样在发光”的误导。

**改动前预期**

- 一旦玩家把某张牌选入某条战线进入待确认状态，就只保留该战线的部署高亮。
- 其它战线仍可以保留普通可读状态，但不再继续显示“当前这张牌可以投放到这里”的高亮反馈。
- 用户取消这次待确认操作后，再恢复为正常的多战线可选提示。

**实际改动后的效果**

- 旗位是否显示部署高亮，统一改由 `canDropOnFlag()` 判定。
- 当 `pendingDeployment` 已存在时：
  - 只有目标旗位会继续返回可高亮状态
  - 其它原本可部署的旗位会被降回普通展示，不再同步点亮
- 因为拖拽态、点击选中旗位和 DOM 同步都复用了同一个判断，所以行为现在是一致的。

**结果与预期对比**

- 与预期一致。
- 这轮没有改变后端规则或真正的可出牌范围，只是把“待确认”阶段的视觉反馈收束到单一目标上。
- 用户如果想改投别的旗位，仍然需要先取消当前待确认操作，这和现有确认流程保持一致。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 25. 拆分 Seats / Orders 并把部署确认收回 Orders

**动机**

- 左栏原本把 `Seats & Orders` 混在一起，而右栏同时还放着 `Deployment preview`，信息分布有些绕。
- 用户希望把座位信息挪到右栏，与 `Field Intel` 放在一起；同时去掉单独的部署预览面板，把确认按钮直接放回操作区。

**改动前预期**

- 左栏专注 `Turn + Orders`。
- 右栏承载 `Seats + Field Intel`。
- 待确认部署时，不再依赖右栏 `Deployment preview`，而是在原先 `Finish Turn` 的位置显示 `Confirm Deployment`。

**实际改动后的效果**

- 左栏标题改成了 `Orders`，内部只保留回合区和操作区。
- `Commander / Opponent` 两个座位卡片整体移到了右栏。
- 右栏去掉了 `Deployment preview` 面板，只保留座位区和 `Field Intel`。
- 当玩家把卡牌暂存到某条战线后：
  - `Orders` 第一颗按钮会变成 `Cancel Deployment`
  - 原本 `Finish Turn` 的位置会变成 `Confirm Deployment`
  - 下方操作说明会显示当前暂存的卡牌和目标旗位
- `Field Intel` 和浮层提示文案也同步改成引导用户去 `Orders` 完成确认。

**结果与预期对比**

- 与预期一致。
- 这轮把“状态阅读”与“确认操作”重新分区：
  - 右栏负责看人和看局势
  - 左栏负责真正发令
- 部署确认路径更短了，用户不需要再把视线来回切到右栏底部。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
- `vendor/bin/pint --dirty --format agent`

---

### 26. 顶栏精简并将 Field Intel 改成点击弹窗

**动机**

- 顶栏里的 `Back` 和状态徽标会占用视觉注意力，而战斗页现在更需要把空间留给局面本身。
- `Field Intel` 常用但不必始终常驻右栏，改成按需呼出会让主界面更干净。

**改动前预期**

- 去掉顶栏中的 `Back` 和状态按钮。
- 把 `Battle #id` 设计成点击入口。
- 点击后弹出 `Field Intel` 浮窗，而不是继续在右栏常驻显示。

**实际改动后的效果**

- 顶栏中央现在只保留一个可点击的 `Battle #id` 区块，仍显示双方玩家名。
- `Back` 按钮和顶部状态徽标已经移除。
- 右栏现在只保留 `Seats`，不再内嵌 `Field Intel`。
- 新增固定定位的 `Field Intel` 弹窗：
  - 点击顶栏 `Battle #id` 打开
  - 点击 `Close`、弹窗外遮罩，或按 `Escape` 关闭
- 原有 `data-feedback` 渲染结果没有丢失，而是改为渲染进弹窗内容区。

**结果与预期对比**

- 与预期一致。
- 顶栏更干净，右栏也更专注于座位信息。
- `Field Intel` 仍然保留原有信息密度，只是从常驻面板改成了按需查看的情报浮层。

**验证**

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 27. 拖拽战线高亮改成单一色阶

**动机**

- 拖拽卡牌经过可部署战线时，当前目标战线会同时保留默认的可投放色和聚焦色，视觉上像是两层颜色在打架。
- 这会削弱“我现在正悬停在哪一条线”的明确性。

**改动前预期**

- 普通可投放战线只保留一种基础高亮色。
- 当前悬停战线或待确认目标战线切到另一种聚焦色。
- 两种状态互斥，不再在同一条战线上叠加。

**实际改动后的效果**

- 战线外层与中间提示区都改成了统一的“双状态”逻辑：
  - 普通可投放：琥珀色
  - 当前悬停 / 待确认目标：金色
- 待确认目标旗位在初始渲染时也不再带着一层琥珀色底样式。
- 因为 `syncBoardState()` 现在显式区分默认高亮和聚焦高亮，所以拖拽经过目标旗位时只会看到单一聚焦色。

**结果与预期对比**

- 与预期一致。
- 这轮没有修改任何规则或操作流程，只收敛了拖拽反馈的视觉层次。
- 当前读感会更接近“可落点”和“当前落点”这两个清晰层级，而不是颜色混合提示。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 28. 状态栏沉浸式改版与去缩放高亮

**动机**

- 全局状态栏左侧的 `Battle Line` 标签和右侧分离的 `Commander / Sign out` 会让顶栏显得碎。
- 对局页允许右键，以及通过缩放来强调选中卡牌和高亮战线，也会破坏沉浸感，尤其在紧凑布局里容易把元素撑出边界。

**改动前预期**

- 去掉顶栏左侧 `Battle Line` 标签，把 `Command Hall` 做成独立按钮。
- 把 `Commander` 和 `Sign out` 合并成一个下拉入口。
- 在对局页禁用右键菜单。
- 把“选中 / 高亮”从放大动效改成更克制的边框、ring 和阴影反馈。

**实际改动后的效果**

- 全局状态栏左侧现在只保留一个 `Command Hall` 按钮。
- 已登录状态下，右侧账号区改成了原生 `details` 下拉：
  - 按钮显示 `Commander {name}`
  - 展开后提供 `Sign out`
- 对局页现在会阻止右键菜单弹出。
- 手牌选中态去掉了放大和上移，改成：
  - 更明确的边框 / ring
  - 更厚一点的阴影
- 战线和中间部署提示区的高亮也去掉了放大 / 位移，只保留颜色与阴影层次。

**结果与预期对比**

- 与预期一致。
- 顶栏信息密度更低，账号操作收得更整洁。
- 对局页在拖拽、选牌和悬停时更稳，不会再出现元素往边框外“鼓出来”的感觉。

**验证**

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 29. 账号下拉层级修正并补开合动画

**动机**

- 当前账号下拉会被下方战线卡片遮住，说明它受到了顶栏自身 stacking context 的限制。
- 既然你已经确认要继续打磨壳层交互，这一轮顺手把账号下拉和 `Battle #id` 情报浮窗的开合观感一起补好更合适。

**改动前预期**

- 让账号下拉稳定盖在战线之上。
- 点外部区域或按 `Escape` 能收起账号下拉。
- 让账号下拉和情报浮窗的进入 / 退出都更顺一些。

**实际改动后的效果**

- 顶栏容器本身现在提升到了更高层级，并显式允许溢出显示。
- 账号下拉在打开状态下会再抬高一层，因此不会再被战线卡片盖住。
- 账号下拉新增了更柔和的：
  - 透明度过渡
  - 轻微下移 / 缩放过渡
- 全局脚本新增了账号菜单交互收口：
  - 点击外部关闭
  - 按 `Escape` 关闭
- `Battle #id` 对应的 `Field Intel` 浮窗也补成了更完整的淡入 + 位移 + 缩放过渡。

**结果与预期对比**

- 与预期一致。
- 这轮没有再动战斗规则和操作流程，只把壳层控件的层级和观感收得更完整。
- 账号菜单现在既不会被挡住，也比之前更像一个成型的弹出层组件。

**验证**

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 30. 战线滚动带补内边距以避免描边裁切

**动机**

- 当前战线卡片在获得金色 ring 时，描边会紧贴横向滚动容器边缘，导致局部显示不完整。
- 根因更像是滚动带留白不够，而不是战线卡片本身的高亮逻辑有误。

**改动前预期**

- 给战线横向滚动带补一点内边距，让 ring 有安全区。
- 顺手把手牌滚动带也做同样处理，避免同类问题在卡牌选中态再次出现。

**实际改动后的效果**

- `Battlefield` 里的 `data-board` 从只有底部留白，改成四周都有一圈轻量内边距。
- `Your Hand` 里的 `data-hand` 也同步增加了相同的横向 / 纵向缓冲。
- 战线金色描边和手牌选中 ring 现在会更完整地显示在滚动区域内，不容易被边缘裁掉。

**结果与预期对比**

- 与预期一致。
- 这轮没有调整战线卡片的高亮样式本身，只给滚动容器补了呼吸空间。
- 这样后续如果继续微调 ring 宽度，容器层面也会更稳一些。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 31. 战线描边改成 inset ring

**动机**

- 在滚动带补了留白后，战线金色描边已经更完整，但本质上仍然依赖父容器给外扩 ring 留空间。
- 既然你接受更稳妥的方案，把战线高亮直接改成 inset 会更彻底。

**改动前预期**

- 战线的高亮 ring 改为画在卡片内部。
- 即使后续再微调滚动容器留白，战线描边也不容易再次被裁切。

**实际改动后的效果**

- 战线卡片外层现在统一带上了 `ring-inset`。
- 当前可部署、悬停、待确认和已占领等依赖 ring 的高亮都会改为向内绘制。
- 描边依旧保留原来的颜色层级，只是从“外扩描边”变成了“内嵌描边”。

**结果与预期对比**

- 与预期一致。
- 这一轮和上一轮容器留白是叠加关系：
  - 滚动带有更舒适的缓冲
  - 战线描边本身也不再依赖外部空间
- 这样后续继续打磨战线高亮时，稳定性会更高。

**验证**

- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`

---

### 32. Field Intel 改成九线总览情报板

**动机**

- 现有 `Field Intel` 更像“当前阶段提示卡”，能告诉玩家下一步该做什么，但还不够像真正的战场情报。
- 你希望它能一眼覆盖当前 9 条战线的总概览，这样打开浮窗时就不只是读提示，而是能快速扫盘。

**改动前预期**

- 保留现有的阶段建议与关键提示。
- 在同一个 `Field Intel` 浮窗里补上对 9 条战线的总览。
- 让玩家能快速看出：
  - 哪些旗已占领
  - 哪些旗有压力
  - 哪些旗可部署 / 可 claim / 待确认

**实际改动后的效果**

- `Field Intel` 浮窗加宽，改成了左右双区结构：
  - 左侧保留当前阶段判断、检查项和关键指标
  - 右侧新增 `Nine-Flag Overview`
- 九线总览会为每条旗生成一张小情报卡，显示：
  - 旗位编号
  - 当前状态标签
  - 简短说明
  - `You / Enemy` 当前兵力数
- 总览状态会根据当前局面自动切换：
  - `Secured`
  - `Lost`
  - `Queued`
  - `Claim Ready`
  - `Deployable`
  - `Formed`
  - `Pressured`
  - `Open`
- 浮窗左侧还新增了更面向全局的摘要指标：
  - `Your Flags`
  - `Enemy Flags`
  - `Contested`
  - `Pressured`

**结果与预期对比**

- 与预期一致。
- 这轮让 `Field Intel` 从“操作提示卡”升级成了“操作提示 + 战场态势图”。
- 玩家现在打开浮窗后，不需要逐条横向滚动战线，也能先完成一轮全局态势判断。

**验证**

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --compact tests/Feature/BattleLineGamePageTest.php`
- `php artisan test --compact`
- `npm run build`
