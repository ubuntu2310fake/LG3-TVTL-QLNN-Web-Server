<?php
require_once 'includes/header.php';

$currentUser = $_SESSION['user'] ?? null;
$currentUserId = $currentUser['id'] ?? 0;
?>

<div class="chess-container">
    <div class="chess-header">
        <h2><i class="fa-solid fa-chess"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ vua' : 'Chess') ?></h2>
        <button id="btn-back-menu" class="btn-back" style="display:none;" onclick="backToMenu()"><i class="fa-solid fa-arrow-left"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quay lại' : 'Back') ?></button>
    </div>

    <!-- Mode Selection -->
    <div id="mode-selection" class="mode-grid">
        <div class="mode-card" onclick="startBotMode()">
            <i class="fa-solid fa-robot mode-icon bot"></i>
            <h3 class="mode-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chế độ Máy' : 'vs Computer') ?></h3>
            <p class="mode-desc"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chơi với Máy' : 'Play vs Computer') ?></p>
        </div>
        <div class="mode-card" onclick="showHumanMode()">
            <i class="fa-solid fa-users mode-icon human"></i>
            <h3 class="mode-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chế độ Người' : 'vs Player') ?></h3>
            <p class="mode-desc"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chơi với bạn bè' : 'Play with a Friend') ?></p>
        </div>
    </div>

    <!-- Game Area -->
    <div id="game-area" class="game-area">
        <div class="game-header">
            <h4 id="game-status" class="game-status-text"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lượt của bạn' : 'Your turn') ?></h4>
            <button class="btn-resign" onclick="resignGame()"><i class="fa-solid fa-flag"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đầu hàng' : 'Resign') ?></button>
        </div>
        
        <div class="game-body">
            <!-- Player 2 (Top) -->
            <div class="player-info">
                <img id="p2-avatar" src="/static/default.png" class="player-avatar">
                <div style="flex-grow: 1;">
                    <h5 class="player-name" id="black-name">Bot (Random)</h5>
                    <div id="captured-by-black" class="captured-area"></div>
                </div>
            </div>

            <!-- Chessboard -->
            <div class="board-wrapper">
                <div id="myBoard"></div>
            </div>

            <!-- Player 1 (Bottom) -->
            <div class="player-info">
                <img id="p1-avatar" src="<?= !empty($currentUser['avatar']) ? '/'.$currentUser['avatar'] : '/static/default.png' ?>" class="player-avatar">
                <div style="flex-grow: 1;">
                    <h5 class="player-name" id="white-name"><?= $currentUser['full_name'] ?? $currentUser['username'] ?></h5>
                    <div id="captured-by-white" class="captured-area"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [data-theme="dark"] [style*="#fff7ed"], [data-theme="dark"] [style*="#fef9c3"], [data-theme="dark"] [style*="#e2e8f0"], [data-theme="dark"] [style*="#f1f5f9"], [data-theme="dark"] [style*="#f8fafc"], [data-theme="dark"] [style*="#fef3c7"], [data-theme="dark"] [style*="#eff6ff"], [data-theme="dark"] [style*="#f0fdf4"] { background: #111111 !important; color: var(--text-main) !important; border-color: var(--border-color) !important; }

.chess-container { width: 95%; max-width: 900px; margin: 30px auto; }
.chess-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.chess-header h2 { color: var(--text-heading); margin: 0; font-size: 1.8rem; font-weight: 700; }

.mode-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
.mode-card {
    background: var(--bg-card); border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    padding: 40px 20px; text-align: center; border: 1px solid var(--border-color);
    cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
}
.mode-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
.mode-icon { font-size: 4.5rem; margin-bottom: 15px; }
.mode-icon.bot { color: var(--primary-color); }
.mode-icon.human { color: #10b981; }
.mode-title { font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
.mode-desc { color: var(--text-muted); font-size: 1rem; margin: 0; }

.game-area {
    display: none; background: var(--bg-card); border-radius: 20px; 
    box-shadow: 0 4px 25px rgba(0,0,0,0.08); border: 1px solid var(--border-color);
    overflow: hidden; margin-bottom: 30px;
}
.game-header {
    background: var(--bg-body); padding: 15px 25px; border-bottom: 1px solid var(--border-color);
    display: flex; justify-content: space-between; align-items: center;
}
.btn-back {
    background: transparent; border: 1px solid var(--border-color); color: var(--text-muted);
    padding: 8px 16px; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-back:hover { background: var(--bg-hover); color: var(--text-main); }
.game-status-text { font-size: 1.2rem; font-weight: 700; color: var(--primary-color); margin: 0; }
.btn-resign {
    background: var(--danger-color); color: #fff; border: none; padding: 8px 20px;
    border-radius: 20px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; font-size: 0.95rem;
}
.btn-resign:hover { opacity: 0.9; }

.game-body { padding: 30px 20px; display: flex; flex-direction: column; align-items: center; }
.player-info { display: flex; align-items: center; width: 100%; max-width: 500px; margin: 10px 0; }
.player-avatar { width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-color); margin-right: 15px; }
.player-name { font-weight: 700; color: var(--text-main); margin: 0 0 5px 0; font-size: 1.15rem; }
.captured-area { display: flex; flex-wrap: wrap; min-height: 24px; gap: 4px; }

.board-wrapper { width: 100%; max-width: 500px; margin: 20px 0; border: 5px solid #2d3748; border-radius: 6px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }

.player-list-ui { list-style: none; padding: 0; margin: 0; text-align: left; }
.player-list-item { 
    display: flex; justify-content: space-between; align-items: center; 
    padding: 12px 15px; border-bottom: 1px solid #e2e8f0;
}
.player-list-item:last-child { border-bottom: none; }
.pl-info { display: flex; align-items: center; }
.pl-avatar-wrap { position: relative; margin-right: 15px; }
.pl-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; }
.pl-dot { 
    position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; 
    border-radius: 50%; border: 2px solid #fff; 
}
.pl-dot.online { background: #10b981; }
.pl-dot.offline { background: #94a3b8; }
.pl-name { font-weight: 600; color: #1e293b; font-size: 1rem; }
.btn-challenge {
    background: transparent; border: 1px solid var(--primary-color); color: var(--primary-color);
    padding: 6px 15px; border-radius: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-challenge:hover { background: #005fba; color: #ffffff !important; }

.highlight-dot {
    background: rgba(0, 0, 0, 0.2); border-radius: 50%;
    width: 30%; height: 30%; position: absolute;
    top: 50%; left: 50%; transform: translate(-50%, -50%);
    pointer-events: none; z-index: 10;
}
.highlight-selected {
    background: rgba(20, 85, 30, 0.5) !important;
}
.captured-piece {
    width: 26px; height: 26px; margin-right: -8px;
    filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.3));
}
.captured-b {
    background: radial-gradient(circle, rgba(255,255,255,0.5) 40%, transparent 70%);
    border-radius: 50%;
}
#myBoard { touch-action: none; }

@media (max-width: 600px) {
    .game-header { padding: 10px 15px; }
    .game-body { padding: 15px 5px; }
    .player-info { margin: 5px 0; max-width: 100%; }
    .board-wrapper { border-width: 3px; margin: 10px 0; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .player-avatar { width: 45px; height: 45px; margin-right: 10px; border-width: 2px; }
    .player-name { font-size: 1rem; margin-bottom: 2px; }
    .captured-piece { width: 22px; height: 22px; margin-right: -6px; }
    .game-status-text { font-size: 1rem; }
    .btn-back { padding: 6px 12px; font-size: 0.9rem; }
    .btn-resign { padding: 6px 15px; font-size: 0.9rem; }
}

/* CUSTOM DROPDOWN CSS (Từ gate_check_view) */
.custom-select-container { position: relative; width: 100%; margin-bottom: 15px; text-align: left; }
.select-selected {
    background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 10px;
    padding: 0 15px; display: flex; align-items: center; justify-content: space-between;
    font-size: 15px; height: 50px; box-sizing: border-box; color: var(--text-main);
    transition: 0.2s; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.select-selected:active { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(0,95,186,0.15); }
.select-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--text-muted); transition: 0.2s; flex-shrink: 0; }
.select-selected.active .select-arrow { transform: rotate(180deg); }
.select-items {
    position: absolute; top: 110%; left: 0; right: 0; z-index: 1000;
    background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px;
    max-height: 300px; overflow-y: auto; display: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease;
}
.select-items div { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
.select-items div:hover { background: var(--bg-hover); color: var(--accent-color); font-weight: 500; }
</style>

<script>
var board = null;
var game = null;
var currentMode = null; // 'bot' or 'human'
var currentMatchId = null;
var myColor = 'w';
var isMyTurn = true;
var matchActive = false; // Prevents moves when game is over
var opponentId = null;
var selectedSquare = null;
var lastSquareClickTime = 0;
var stockfish = null;
var botDifficulty = 'medium';

// ================= SPA INITIALIZATION =================
function loadScriptVanilla(url, callback) {
    if (document.querySelector(`script[src="${url}"]`)) {
        if (callback) callback();
        return;
    }
    let script = document.createElement('script');
    script.src = url;
    script.onload = callback;
    document.head.appendChild(script);
}

window.pageInit = function() {
    if (typeof Worker !== 'undefined' && !stockfish) {
        stockfish = new Worker('/static/js/stockfish.js');
        stockfish.onmessage = function(event) {
            let line = event.data;
            if (line.indexOf('bestmove') > -1) {
                let match = line.match(/^bestmove ([a-h][1-8])([a-h][1-8])([qrob])?/);
                if (match) {
                    let move = {
                        from: match[1],
                        to: match[2],
                        promotion: match[3] || 'q'
                    };
                    let result = game.move(move);
                    if (result) {
                        board.position(game.fen());
                        isMyTurn = true;
                        updateStatus();
                    }
                }
            }
        };
    }

    if (!document.getElementById('chessboard-css')) {
        let link = document.createElement('link');
        link.id = 'chessboard-css';
        link.rel = 'stylesheet';
        link.href = '/static/css/chessboard-1.0.0.min.css';
        document.head.appendChild(link);
    }

    loadScriptVanilla('/static/js/jquery-3.6.0.min.js', function() {
        loadScriptVanilla('/static/js/chess.js', function() {
            loadScriptVanilla('/static/js/chessboard-1.0.0.js', function() {
                game = new Chess();
                
                const urlParams = new URLSearchParams(window.location.search);
                const matchId = urlParams.get('match_id');
                if (matchId) {
                    joinMatch(matchId);
                }

                if (window.SSEManager) {
                    SSEManager.on('CHESS_MOVE', handleMove);
                    SSEManager.on('CHESS_RESIGN', handleResign);
                    if (typeof window.initGlobalSSEListeners === 'function') window.initGlobalSSEListeners();
                }
            });
        });
    });
};

window.pageDestroy = function() {
    if (window.SSEManager) {
        SSEManager.off('CHESS_MOVE', handleMove);
        SSEManager.off('CHESS_RESIGN', handleResign);
        SSEManager.off('CHESS_ACCEPTED', handleAccepted);
        SSEManager.off('CHESS_DECLINED', handleDeclined);
    }
    if (board && typeof board.destroy === 'function') {
        board.destroy();
    }
    game = null;
    board = null;
};

function initBoard() {
    document.getElementById('mode-selection').style.display = 'none';
    document.getElementById('game-area').style.display = 'block';
    document.getElementById('btn-back-menu').style.display = 'inline-block';
    
    let config = {
        pieceTheme: '/static/img/chesspieces/wikipedia/{piece}.png',
        draggable: true,
        position: 'start',
        onDragStart: onDragStart,
        onDrop: onDrop,
        onSnapEnd: onSnapEnd,
        onMouseoverSquare: onMouseoverSquare,
        onMouseoutSquare: onMouseoutSquare,
        orientation: myColor === 'w' ? 'white' : 'black'
    };
    if (board) board.destroy();
    board = Chessboard('myBoard', config);
    window.addEventListener('resize', board.resize);
    
    // Add click-to-move for mobile (empty squares or opponent pieces)
    $(document).off('click', '#myBoard .square-55d63').on('click', '#myBoard .square-55d63', function() {
        var square = $(this).attr('data-square');
        handleSquareClick(square);
    });
}

function handleSquareClick(square) {
    if (!matchActive || !game || game.game_over() || !isMyTurn) return;
    
    // Debounce to prevent double trigger from onDrop and click
    var now = Date.now();
    if (now - lastSquareClickTime < 100) return;
    lastSquareClickTime = now;

    if (selectedSquare) {
        if (selectedSquare === square) {
            selectedSquare = null;
            removeHighlights();
            return;
        }
        let move = game.move({
            from: selectedSquare,
            to: square,
            promotion: 'q'
        });
        if (move === null) {
            let piece = game.get(square);
            if (piece && piece.color === myColor) {
                selectedSquare = square;
                highlightLegalMoves(square);
            } else {
                selectedSquare = null;
                removeHighlights();
            }
        } else {
            selectedSquare = null;
            removeHighlights();
            board.position(game.fen());
            processValidMove(); 
        }
    } else {
        let piece = game.get(square);
        if (piece && piece.color === myColor) {
            selectedSquare = square;
            highlightLegalMoves(square);
        }
    }
}

function highlightLegalMoves(square) {
    removeHighlights();
    var moves = game.moves({ square: square, verbose: true });
    if (moves.length === 0) return;
    for (var i = 0; i < moves.length; i++) {
        var targetSquare = moves[i].to;
        var squareEl = $('#myBoard .square-' + targetSquare);
        if (squareEl.find('.highlight-dot').length === 0) {
            squareEl.append('<div class="highlight-dot"></div>');
        }
    }
    $('#myBoard .square-' + square).addClass('highlight-selected');
}

function backToMenu() {
    if (matchActive) {
        WinUI.confirm(
            '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn đang trong ván đấu!' : 'You are in a match!')) ?>',
            '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thoát ra bây giờ đồng nghĩa với việc bạn nhận thua. Chắc chắn thoát?' : 'Leaving now means you resign. Are you sure?')) ?>',
            () => {
                if (currentMode === 'human') {
                    fetch('api/chess_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=resign&match_id=' + currentMatchId
                    }).then(() => goBack());
                } else {
                    goBack();
                }
            }
        );
    } else {
        goBack();
    }
}

function goBack() {
    matchActive = false;
    isMyTurn = false;
    currentMode = null;
    document.getElementById('game-area').style.display = 'none';
    document.getElementById('btn-back-menu').style.display = 'none';
    document.getElementById('mode-selection').style.display = 'grid';
    if (window.history.pushState) {
        let url = new URL(window.location.href);
        url.search = '';
        window.history.pushState({path:url.href}, '', url.href);
    }
}

function removeHighlights() {
    $('#myBoard .square-55d63').find('.highlight-dot').remove();
    $('#myBoard .square-55d63').removeClass('highlight-selected');
}

function onMouseoverSquare(square, piece) {
    if (selectedSquare) return; // Don't hover highlight if clicked
    if (!matchActive || !game || game.game_over() || !isMyTurn) return;
    var moves = game.moves({ square: square, verbose: true });
    if (moves.length === 0) return;
    
    for (var i = 0; i < moves.length; i++) {
        var targetSquare = moves[i].to;
        var squareEl = $('#myBoard .square-' + targetSquare);
        if (squareEl.find('.highlight-dot').length === 0) {
            squareEl.append('<div class="highlight-dot"></div>');
        }
    }
}

function onMouseoutSquare(square, piece) {
    if (selectedSquare) return; // Don't remove hover highlight if clicked
    removeHighlights();
}

// ================= HUMAN MODE (Swal instead of Bootstrap Modal) =================

function showHumanMode() {
    if (!game) {
        WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng đợi' : 'Please wait')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải dữ liệu cờ vua...' : 'Loading chess data...')) ?>');
        return;
    }
    
    window.currentHumanPopup = WinUI.popup(
        '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chơi với bạn bè' : 'Play with a Friend')) ?>',
        '<div id="swal-player-list"><div style="padding: 20px; text-align: center; color: var(--text-muted);">Đang tải...</div></div>'
    );
    
    fetch('api/chess_api.php?action=get_players')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                let html = '<ul class="player-list-ui">';
                if (res.players.length === 0) {
                    html += '<li class="player-list-item" style="justify-content:center; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa có bạn bè' : 'No friends yet') ?></li>';
                } else {
                    res.players.forEach(p => {
                        let badge = p.role === 'TEACHER' ? '<span style="font-size:10px; background:#005fba; color: #ffffff !important; padding:2px 6px; border-radius:10px; margin-left:8px;">Teacher</span>' : '';
                                let dotClass = p.is_online ? 'online' : 'offline';
                                html += `
                                    <li class="player-list-item">
                                        <div class="pl-info">
                                            <div class="pl-avatar-wrap">
                                                <img src="${p.avatar ? '/' + p.avatar : '/static/default.png'}" class="pl-avatar">
                                                <span class="pl-dot ${dotClass}"></span>
                                            </div>
                                            <div class="pl-name">${p.full_name || p.username} ${badge}</div>
                                        <button class="btn-challenge" onclick="if(window.currentHumanPopup) window.currentHumanPopup.close(); sendChallenge(${p.id})"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thách đấu' : 'Challenge') ?></button>
                                    </li>
                                `;
                            });
                        }
                        html += '</ul>';
                        document.getElementById('swal-player-list').innerHTML = html;
                    } else {
                        document.getElementById('swal-player-list').innerHTML = '<div style="color:var(--danger-color); padding: 20px;">Lỗi tải dữ liệu.</div>';
                    }
                })
                .catch(() => {
                    document.getElementById('swal-player-list').innerHTML = '<div style="color:var(--danger-color); padding: 20px;">Lỗi mạng.</div>';
                });
}

function sendChallenge(targetId) {
    Swal.fire({ title: 'Đang gửi lời mời...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch('api/chess_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=challenge&target_id=' + targetId
    }).then(res => res.json()).then(res => {
        if (res.status === 'success') {
            currentMatchId = res.match_id;
            Swal.fire({
                title: 'Đã gửi lời mời!',
                text: 'Đang chờ đối thủ đồng ý...',
                icon: 'info',
                showCancelButton: true,
                cancelButtonText: 'Hủy lời mời',
                showConfirmButton: false,
                allowOutsideClick: false
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    fetch('api/chess_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=cancel_challenge&match_id=' + currentMatchId
                    });
                }
            });
            if (window.SSEManager) {
                SSEManager.on('CHESS_ACCEPTED', handleAccepted);
                SSEManager.on('CHESS_DECLINED', handleDeclined);
            }
        } else {
            Swal.fire('Lỗi', res.msg, 'error');
        }
    });
}

function handleAccepted(data) {
    if (data.match_id == currentMatchId) {
        Swal.close();
        joinMatch(currentMatchId);
    }
}

function handleDeclined(data) {
    if (data.match_id == currentMatchId) {
        Swal.fire('Bị từ chối', 'Đối thủ đã từ chối lời mời của bạn.', 'warning');
    }
}

function joinMatch(matchId) {
    fetch('api/chess_api.php?action=match_info&match_id=' + matchId)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                let match = res.match;
                currentMatchId = match.id;
                currentMode = 'human';
                matchActive = true;
                
                myColor = match.your_color;
                isMyTurn = match.is_your_turn;
                
                let selfPlayer = myColor === 'w' ? match.white_player : match.black_player;
                let oppPlayer  = myColor === 'w' ? match.black_player : match.white_player;
                opponentId = oppPlayer.id;
                
                // Khung dưới (Player 1 / White UI box) luôn hiển thị CHÍNH BẠN
                document.getElementById('white-name').innerText = selfPlayer.name;
                document.getElementById('p1-avatar').src = selfPlayer.avatar ? '/' + selfPlayer.avatar : '/static/default.png';
                
                // Khung trên (Player 2 / Black UI box) luôn hiển thị ĐỐI THỦ
                document.getElementById('black-name').innerText = oppPlayer.name;
                document.getElementById('p2-avatar').src = oppPlayer.avatar ? '/' + oppPlayer.avatar : '/static/default.png';
                
                game.load(match.fen);
                
                initBoard();
                updateStatus();
            } else {
                WinUI.alert('Error', res.msg);
            }
        });
}

// ================= BOT MODE =================

function startBotMode() {
    if (!game) {
        WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng đợi' : 'Please wait')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải dữ liệu cờ vua...' : 'Loading chess data...')) ?>');
        return;
    }
    
    if (!stockfish) {
        WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi hệ thống' : 'System Error')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trình duyệt của bạn không hỗ trợ Web Worker hoặc file Stockfish.js bị thiếu!' : 'Your browser does not support Web Workers or Stockfish.js is missing!')) ?>');
        return;
    }

    let content = `
        <div style="margin-bottom: 10px; font-weight: 500; font-size: 15px; color: var(--text-main);"><?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng chọn mức độ thông minh của AI:' : 'Please select AI intelligence level:')) ?></div>
        <div class="custom-select-container">
            <div role="button" tabindex="0" class="select-selected" onclick="toggleDropdown(event, this)">
                <span id="txtSelectedDifficulty" style="font-weight:600;"><?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟨 Trung bình (Nghiệp dư)' : '🟨 Medium (Amateur)')) ?></span>
                <div class="select-arrow"></div>
            </div>
            <div class="select-items">
                <div role="button" tabindex="0" onclick="selectDifficulty('easy', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟩 Dễ (Học việc)' : '🟩 Easy (Beginner)')) ?>', this)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟩 Dễ (Học việc)' : '🟩 Easy (Beginner)') ?></div>
                <div role="button" tabindex="0" onclick="selectDifficulty('medium', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟨 Trung bình (Nghiệp dư)' : '🟨 Medium (Amateur)')) ?>', this)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟨 Trung bình (Nghiệp dư)' : '🟨 Medium (Amateur)') ?></div>
                <div role="button" tabindex="0" onclick="selectDifficulty('hard', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟧 Khó (Chuyên nghiệp)' : '🟧 Hard (Professional)')) ?>', this)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟧 Khó (Chuyên nghiệp)' : '🟧 Hard (Professional)') ?></div>
                <div role="button" tabindex="0" onclick="selectDifficulty('master', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟥 Siêu khó (Đại kiện tướng)' : '🟥 Master (Grandmaster)')) ?>', this)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🟥 Siêu khó (Đại kiện tướng)' : '🟥 Master (Grandmaster)') ?></div>
            </div>
            <input type="hidden" id="botDifficultySelector" value="medium">
        </div>
    `;

    WinUI.confirm('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cấp độ Bot' : 'Bot Difficulty')) ?>', content, () => {
        let diff = document.getElementById('botDifficultySelector');
        if (diff) {
            setupBotGame(diff.value);
        } else {
            setupBotGame('medium');
        }
    });
}

function setupBotGame(difficulty) {
    botDifficulty = difficulty;
    currentMode = 'bot';
    myColor = 'w';
    isMyTurn = true;
    matchActive = true;
    game.reset();
    
    let botName = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bot (Trung bình)' : 'Bot (Medium)')) ?>';
    let skillLevel = 5;
    
    if (difficulty === 'easy') { botName = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bot (Dễ)' : 'Bot (Easy)')) ?>'; skillLevel = 0; }
    if (difficulty === 'hard') { botName = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bot (Khó)' : 'Bot (Hard)')) ?>'; skillLevel = 10; }
    if (difficulty === 'master') { botName = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bot (Siêu khó)' : 'Bot (Master)')) ?>'; skillLevel = 20; }
    
    document.getElementById('white-name').innerText = '<?= $currentUser["full_name"] ?? $currentUser["username"] ?>';
    document.getElementById('black-name').innerText = botName;
    updateStatus();
    initBoard();
    
    stockfish.postMessage('uci');
    stockfish.postMessage('setoption name Skill Level value ' + skillLevel);
}

function makeBotMove() {
    if (!game || !matchActive) return;
    document.getElementById('game-status').innerText = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Máy đang suy nghĩ...' : 'Bot is thinking...')) ?>';
    
    let depth = 5;
    let movetime = 1000;
    if (botDifficulty === 'easy') { depth = 1; movetime = 500; }
    if (botDifficulty === 'hard') { depth = 10; movetime = 2000; }
    if (botDifficulty === 'master') { depth = 15; movetime = 3000; }
    
    stockfish.postMessage('position fen ' + game.fen());
    stockfish.postMessage('go depth ' + depth + ' movetime ' + movetime);
}

// ================= BOARD LOGIC =================

function onDragStart(source, piece, position, orientation) {
    if (!matchActive || !game || game.game_over()) return false;
    if (!isMyTurn) return false;
    
    if ((myColor === 'w' && piece.search(/^b/) !== -1) ||
        (myColor === 'b' && piece.search(/^w/) !== -1)) {
        return false;
    }
}

function processValidMove() {
    isMyTurn = false;
    updateStatus();

    if (currentMode === 'bot') {
        if (matchActive) {
            document.getElementById('game-status').innerText = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Máy đang suy nghĩ...' : 'Bot is thinking...')) ?>';
            makeBotMove();
        }
    } else {
        let gameStatus = 'PLAYING';
        let winnerId = null;
        if (!matchActive) {
            gameStatus = 'FINISHED';
            if (game.in_checkmate()) winnerId = <?= $currentUserId ?>;
        }
        
        fetch('api/chess_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=move&match_id=${currentMatchId}&fen=${encodeURIComponent(game.fen())}&game_status=${gameStatus}&winner_id=${winnerId || ''}`
        });
    }
}

function onDrop(source, target) {
    if (source === target) {
        handleSquareClick(source);
        return 'snapback';
    }

    let move = game.move({
        from: source,
        to: target,
        promotion: 'q'
    });

    if (move === null) return 'snapback';

    processValidMove();
}

function onSnapEnd() {
    board.position(game.fen());
}

function updateStatus() {
    if (!game) return;
    let status = '';
    let moveColor = game.turn() === 'w' ? '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trắng' : 'White')) ?>' : '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đen' : 'Black')) ?>';

    if (game.in_checkmate()) {
        status = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>, ' + moveColor + ' <?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'bị chiếu bí' : 'is in checkmate')) ?>.';
        matchActive = false;
        isMyTurn = false;
        if (game.turn() === myColor) {
            WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn đã thua!' : 'You lost!')) ?>');
        } else {
            WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn đã thắng!' : 'You won!')) ?>');
        }
    }
    else if (game.in_draw()) {
        status = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>, <?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thế cờ hòa' : 'Drawn position')) ?>';
        matchActive = false;
        isMyTurn = false;
        WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hòa!' : 'Draw!')) ?>');
    }
    else {
        if (isMyTurn) {
            status = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lượt của bạn' : 'Your turn')) ?>';
        } else {
            status = '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lượt của đối thủ' : "Opponent's turn")) ?>';
        }
        if (game.in_check()) {
            status += ', ' + moveColor + ' <?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'đang bị chiếu' : 'is in check')) ?>';
        }
    }
    document.getElementById('game-status').innerHTML = status;
    updateCapturedPieces();
}

function updateCapturedPieces() {
    if (!game) return;
    
    // Đếm số lượng quân cờ còn lại trên bàn cờ
    const initial = { p: 8, r: 2, n: 2, b: 2, q: 1 };
    let currentWhite = { p: 0, r: 0, n: 0, b: 0, q: 0 };
    let currentBlack = { p: 0, r: 0, n: 0, b: 0, q: 0 };

    const boardState = game.board();
    if (boardState) {
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                let piece = boardState[r][c];
                if (piece) {
                    let type = piece.type;
                    if (type !== 'k') {
                        if (piece.color === 'w') {
                            currentWhite[type] = (currentWhite[type] || 0) + 1;
                        } else {
                            currentBlack[type] = (currentBlack[type] || 0) + 1;
                        }
                    }
                }
            }
        }
    }

    let capturedWhite = []; // Quân Trắng bị ăn (thiếu trên bàn)
    let capturedBlack = []; // Quân Đen bị ăn (thiếu trên bàn)

    ['p', 'r', 'n', 'b', 'q'].forEach(type => {
        let missingW = initial[type] - (currentWhite[type] || 0);
        for (let i = 0; i < missingW; i++) capturedWhite.push(type);

        let missingB = initial[type] - (currentBlack[type] || 0);
        for (let i = 0; i < missingB; i++) capturedBlack.push(type);
    });

    let getImages = (arr, colorChar) => arr.map(p => `<img src="/static/img/chesspieces/wikipedia/${colorChar}${p.toUpperCase()}.png" class="captured-piece captured-${colorChar}">`).join('');
    
    if (myColor === 'w') {
        // Bạn là Trắng (ở dưới) -> ăn quân Đen
        document.getElementById('captured-by-white').innerHTML = getImages(capturedBlack, 'b');
        // Đối thủ là Đen (ở trên) -> ăn quân Trắng
        document.getElementById('captured-by-black').innerHTML = getImages(capturedWhite, 'w');
    } else {
        // Bạn là Đen (ở dưới) -> ăn quân Trắng
        document.getElementById('captured-by-white').innerHTML = getImages(capturedWhite, 'w');
        // Đối thủ là Trắng (ở trên) -> ăn quân Đen
        document.getElementById('captured-by-black').innerHTML = getImages(capturedBlack, 'b');
    }
}

// ================= SSE HANDLERS =================

function handleMove(data) {
    if (data.match_id == currentMatchId) {
        game.load(data.fen);
        board.position(game.fen());
        isMyTurn = true;
        updateStatus();
        
        if (data.game_status === 'FINISHED') {
            matchActive = false;
            isMyTurn = false;
        }
    }
}

function handleResign(data) {
    if (data.match_id == currentMatchId) {
        matchActive = false;
        isMyTurn = false;
        updateStatus();
        WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn đã thắng!' : 'You won!')) ?> (<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đối thủ đã đầu hàng' : 'Opponent resigned')) ?>)');
    }
}

function resignGame() {
    if (!matchActive) return;
    WinUI.confirm(
        '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn có chắc chắn?' : 'Are you sure?')) ?>',
        '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đầu hàng đồng nghĩa với việc bạn nhận thua ván đấu này.' : 'Resigning means you lose this match.')) ?>',
        () => {
            matchActive = false;
            isMyTurn = false;
            if (currentMode === 'bot') {
                WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn đã thua!' : 'You lost!')) ?>');
            } else {
                fetch('api/chess_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=resign&match_id=' + currentMatchId
                }).then(() => {
                    WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ván đấu kết thúc' : 'Game Over')) ?>', '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn đã thua!' : 'You lost!')) ?>');
                });
            }
        }
    );
}

// ================= CUSTOM DROPDOWN LOGIC =================
window.toggleDropdown = function(e, el) { 
    e.stopPropagation(); 
    window.closeAllSelects(el); 
    el.nextElementSibling.style.display = el.nextElementSibling.style.display === 'block' ? 'none' : 'block'; 
    el.classList.toggle('active'); 
};
window.closeAllSelects = function(elmnt) {
    var x, y, i, xl, yl, arrNo = [];
    x = document.getElementsByClassName("select-items");
    y = document.getElementsByClassName("select-selected");
    xl = x.length;
    yl = y.length;
    for (i = 0; i < yl; i++) {
        if (elmnt == y[i]) { arrNo.push(i) } else { y[i].classList.remove("active"); }
    }
    for (i = 0; i < xl; i++) {
        if (arrNo.indexOf(i)) { x[i].style.display = "none"; }
    }
}
document.addEventListener("click", window.closeAllSelects);

window.selectDifficulty = function(value, text, el) {
    document.getElementById('txtSelectedDifficulty').innerText = text;
    document.getElementById('botDifficultySelector').value = value;
    window.closeAllSelects(null);
};
</script>

<?php require_once 'includes/footer.php'; ?>
