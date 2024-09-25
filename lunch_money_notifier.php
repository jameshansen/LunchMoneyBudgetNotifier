<?php
include("lunch_money_notifier_config.php");

function fetchData($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function sendPostRequest($url, $accessToken) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('Curl error in POST request: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

function calculateBalance($data) {
    $total = 0;
    $details = [];
    foreach ($data as $accountGroup => $balances) {
        $accountGroupTotal = 0;
        foreach ($balances as $balance) {
            $amount = floatval($balance['balance']);
            $accountGroupTotal += $amount;
            $total += $amount;
        }
        $details[$accountGroup] = $accountGroupTotal;
    }
    return ['total' => $total, 'details' => $details];
}

function isPendingImport($data) {
    foreach ($data as $accountGroup => $balances) {
        $last_import = null;
        $last_fetch = null;
        
        foreach ($balances as $balance) {
            if (isset($balance['last_import'])) {
                $last_import = new DateTime($balance['last_import']);
            }
            if (isset($balance['last_fetch'])) {
                $last_fetch = new DateTime($balance['last_fetch']);
            }
            
            // If we have both timestamps for this balance, we can compare
            if ($last_import && $last_fetch) {
                // If last_fetch is newer than last_import, we have a pending import
                if ($last_fetch > $last_import) {
                    return true;
                }
            }
        }
    }
    
    // If we've gone through all balances without finding a pending import, return false
    return false;
}


function sendPushoverNotification($message) {
    $url = 'https://api.pushover.net/1/messages.json';
    $data = array(
        'token' => PUSHOVER_TOKEN,
        'user' => PUSHOVER_USER,
        'message' => $message
    );

	print_r($data);
	return;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('Pushover notification failed: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

echo "Script Started at " . date('Y-m-d H:i:s') . "\n";
$lastBudgetLeftFile = __DIR__ . '/last_budget_left.txt';

$lastBudgetLeft = null;
if (file_exists($lastBudgetLeftFile)) {
    $lastBudgetLeft = file_get_contents($lastBudgetLeftFile);
    if ($lastBudgetLeft !== false) {
        $lastBudgetLeft = floatval(trim($lastBudgetLeft));
        echo "Loaded Previous Remaining Budget: " . number_format($lastBudgetLeft, 2) . "\n";
    } else {
        echo "Error reading from file. Starting with null budget.\n";
        $lastBudgetLeft = null;
    }
} else {
    echo "No previous budget file found. Starting fresh.\n";
}

while (true) {
	try {

	$fetchUrl = "https://dev.lunchmoney.app/v1/plaid_accounts/fetch?access_token=" . SITE_TOKEN;
    $assetUrl = "https://dev.lunchmoney.app/v1/assets?access_token=" . SITE_TOKEN;
    $plaidAccountUrl = "https://dev.lunchmoney.app/v1/plaid_accounts?access_token=" . SITE_TOKEN;
	
	// Check Plaid Account Data
	$plaidAccountData = fetchData($plaidAccountUrl);
	
	if(!isPendingImport($plaidAccountData))
	{
		echo "No Pending Plaid Accounts Fetch Requests in Progress at " . date('Y-m-d H:i:s') . "\n";
		$fetchData = sendPostRequest($fetchUrl, SITE_TOKEN);
	
		if($fetchData == 1)
		{
			echo "Successfully sent Plaid Accounts Fetch Request at " . date('Y-m-d H:i:s') . "\n";
		} else {
			echo "Plaid Accounts Fetch Request failed " . date('Y-m-d H:i:s') . ", Output:\n";
			print_r($fetchData);
			echo "\n";
		}
	} else {
		echo "Pending Plaid Accounts Fetch Request is still in Progress at " . date('Y-m-d H:i:s') . "\n";
	}

	$assetData = fetchData($assetUrl);

	$assetBalance = calculateBalance($assetData);
	$plaidAccountBalance = calculateBalance($plaidAccountData);

	$totalBalance = $assetBalance['total'] + $plaidAccountBalance['total'];
	$budgetLeft = SPENDING_BUDGET - $totalBalance;

	$title =  date('F') . " Spending Money Budget Remaining: $" . number_format($budgetLeft, 2);
	$detailedMessage = "";
	$change = 0;

	if ($lastBudgetLeft !== null) {
		$change = round($lastBudgetLeft - $budgetLeft, 2);		
		if ($change != 0) {
			echo "Budget left has changed by $" . $change . "\n";
			if ($change > 0) {
				$title = "New debit: $" . number_format($change, 2) . ". " . $title;
			} else {
				$title = "New credit: $" . number_format(abs($change), 2) . ". " . $title;
			}
			$detailedMessage .= "Change since last check: $" . number_format($change, 2) . "\n\n";
		}
	} else {
		$detailedMessage .= "Initial check.\n\n";
	}

	$detailedMessage .= "Details:\n";
	$combinedDetails = array_merge_recursive($assetBalance['details'], $plaidAccountBalance['details']);
	foreach ($combinedDetails as $accountGroup => $balance) {
		$totalBalance = is_array($balance) ? array_sum($balance) : $balance;
		$detailedMessage .= substr($accountGroup, 0, 8) . "...: " . number_format($totalBalance, 2) . "\n";
	}

	$detailedMessage .= "\nTotal Balance: " . number_format($totalBalance, 2);

	if ($change != 0) {
		$response = sendPushoverNotification($title, $detailedMessage);
		if (isset($response['status']) && $response['status'] == 1) {
			echo "Notification sent successfully at " . date('Y-m-d H:i:s') . "\n";
		} else {
			echo "Failed to send notification at " . date('Y-m-d H:i:s') . "\n";
		}
		$lastBudgetLeft = $budgetLeft;
		// Save the new budget left to the file
		if (file_put_contents($lastBudgetLeftFile, $budgetLeft) === false) {
			echo "Error writing to file at " . date('Y-m-d H:i:s') . "\n";
		} else {
			echo "Updated budget saved to file at " . date('Y-m-d H:i:s') . "\n";
		}
            
	} else {
		echo "No change in budget at " . date('Y-m-d H:i:s') . "\n";
	}
} catch (Exception $e) {
	echo "Error occurred at " . date('Y-m-d H:i:s') . ": " . $e->getMessage() . "\n";
}

sleep(CHECK_INTERVAL);
}