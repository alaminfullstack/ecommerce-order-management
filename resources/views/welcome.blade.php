<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f0f4f8;
            padding-bottom: 20px;
        }

        .header {
            background-color: #FFD700;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }

        .back-button {
            position: absolute;
            left: 15px;
            background-color: transparent;
            border: none;
            color: #1E40AF;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .back-button:active {
            background-color: rgba(30, 64, 175, 0.1);
        }

        .header-icon {
            font-size: 28px;
        }

        .header-title {
            color: #1E40AF;
            font-size: 24px;
            font-weight: bold;
        }

        .balance-section {
            background-color: #1E40AF;
            color: white;
            padding: 30px 20px;
            margin: 0;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        }

        .wallet-balance {
            text-align: center;
            margin-bottom: 25px;
        }

        .balance-icon-wrapper {
            display: inline-block;
            background-color: rgba(255, 215, 0, 0.2);
            padding: 15px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .balance-icon {
            font-size: 36px;
        }

        .wallet-balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .wallet-balance-amount {
            font-size: 42px;
            font-weight: bold;
            margin-top: 5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 25px;
        }

        .stat-item {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 15px 10px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 11px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 20px;
            background-color: transparent;
            margin-top: 15px;
        }

        .btn {
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.2s;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-withdraw {
            background-color: white;
            color: #1E40AF;
            border: 2px solid #FFD700;
        }

        .btn-deposit {
            background-color: #FFD700;
            color: #1E40AF;
        }

        .btn-icon {
            font-size: 20px;
        }

        .coin-section {
            background-color: white;
            margin: 10px 20px 20px;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .coin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .coin-icon {
            width: 55px;
            height: 55px;
            background-color: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 3px 10px rgba(255, 215, 0, 0.4);
        }

        .coin-details h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .coin-balance {
            font-size: 24px;
            font-weight: bold;
            color: #1E40AF;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .transactions-section {
            margin: 0 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .transaction-item {
            background-color: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s;
        }

        .transaction-item:active {
            transform: scale(0.99);
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .transaction-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .icon-invest {
            background-color: #e3f2fd;
            color: #1E40AF;
        }

        .icon-recharge {
            background-color: #fff9c4;
            color: #f57c00;
        }

        .icon-withdraw {
            background-color: #ffebee;
            color: #c62828;
        }

        .icon-bonus {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .transaction-title {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .transaction-amount {
            font-size: 18px;
            font-weight: bold;
            white-space: nowrap;
        }

        .amount-positive {
            color: #2e7d32;
        }

        .amount-negative {
            color: #c62828;
        }

        .transaction-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #666;
            padding-left: 52px;
        }

        .transaction-date {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .date-icon {
            font-size: 14px;
        }

        .transaction-savings {
            background-color: #FFD700;
            color: #1E40AF;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="back-button" onclick="history.back()">←</button>
        <div class="header-icon">💰</div>
        <div class="header-title">My Wallet</div>
    </div>

    <div class="balance-section">
        <div class="wallet-balance">
            <div class="balance-icon-wrapper">
                <div class="balance-icon">🏦</div>
            </div>
            <div class="wallet-balance-label">WALLET BALANCE</div>
            <div class="wallet-balance-amount">${{ showAmount(auth()->user()->interest_wallet) }}</div>
        </div>

        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon">📈</div>
                <div class="stat-label">Total Invest</div>
                <div class="stat-value">${{ showAmount(auth()->user()->invests->sum('amount')) }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">⚡</div>
                <div class="stat-label">Total Recharge</div>
                <div class="stat-value">${{ showAmount(auth()->user()->deposit_wallet) }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">🎯</div>
                <div class="stat-label">Total Savings</div>
                <div class="stat-value">${{ showAmount(auth()->user()->savings_wallet) }}</div>
            </div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="{{route ('user.withdraw')}}" class="btn btn-withdraw">
            <span class="btn-icon">💸</span>
            Withdraw
        </a>

        <a href="{{route('user.deposit.index')}}" class="btn btn-deposit">
            <span class="btn-icon">➕</span>
            Deposit
        </a>
    </div>

    <div class="coin-section">
        <div class="coin-info">
            <div class="coin-icon">🪙</div>
            <div class="coin-details">
                <h3>Expo Coins</h3>
            </div>
        </div>
        <div class="coin-balance">
            {{ getAmount(convertAmount($user->savings_wallet)) }}
            <span style="font-size: 16px;">💎</span>
        </div>
    </div>

    <div class="transactions-section">
        <h2 class="section-title">
            📋 Transactions
        </h2>
        @forelse($transactions as $transaction)
        <div class="transaction-item">
            <div class="transaction-header">
                <div class="transaction-left">
                    <div class="transaction-icon icon-invest">📊</div>
                    <div class="transaction-title">
                        {{ __(keyToTitle($transaction->remark)) }}
                    </div>
                </div>
                <div class="transaction-amount amount-positive">
                    {{ showAmount($transaction->amount) }} {{ $general->cur_text }}
                    {{ $transaction->fromUser->username ?? null }}

                </div>
            </div>
            <div class="transaction-footer">
                <div class="transaction-date">
                    <span class="date-icon">🗓️</span>
                    {{ showDateTime($transaction->created_at, 'M d Y @g:i:a') }}

                </div>
                <div class="transaction-savings">
                    💰 {{ showAmount($transaction->savings) }} {{ $general->cur_text }}
                </div>
            </div>
        </div>
        @empty
        <div class="transaction-item">
            <div class="transaction-header">
                <div class="transaction-left">
                    <div class="transaction-icon icon-recharge">⚡</div>
                    <div class="transaction-title">No Data Found</div>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</body>
</html>