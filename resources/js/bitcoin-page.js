const apiBaseUrl = 'https://blockchain.info/balance?cors=true&active=';
// const apiBaseUrl = 'http://keys.pk/api/v1/realistic-balance?active=';
// const apiBaseUrl = 'http://keys.pk/api/v1/mock-balance?active=';
const showResultDelay = 3000;


let addresses = keys.map(w => w.pub).join('|');

// Checking the balance of some wallets on blockchain.com returns an error.
// first page
if (addresses.indexOf('1EHNa6Q4Jz2uvNExL497mE43ikXhwF6kZm|') !== -1) {
    addresses = addresses.replace('1EHNa6Q4Jz2uvNExL497mE43ikXhwF6kZm|', '');
    addValuesToWallet('5HpHagT65TZzG1PH3CSu63k8DbpvD8s5ip4nEB3kEsreAnchuDf', 0, 1213);
}

// last page
if (addresses.indexOf('1JPbzbsAx1HyaDQoLMapWGoqf9pD5uha5m') !== -1) {
    addresses = addresses.replace('1JPbzbsAx1HyaDQoLMapWGoqf9pD5uha5m', '');
    addValuesToWallet('5Km2kuu7vtFDPpxywn4u3NLpbr5jKpTB3jsuDU2KYEqetqj84qw', 0, 19);
}

// page 694260142966555606892331775748698180356633071060141805117247038861673171840
if (addresses.indexOf('1BFhrfTTZP3Nw4BNy4eX4KFLsn9ZeijcMm|') !== -1) {
    addresses = addresses.replace('1BFhrfTTZP3Nw4BNy4eX4KFLsn9ZeijcMm|', '');
    addValuesToWallet('5KJp7KEffR7HHFWSFYjiCUAntRSTY69LAQEX1AUzaSBHHFdKEpQ', 0, 165);
}

axios.get(apiBaseUrl + addresses).then(response => {
    keys.forEach(key => {
        sleepRandom(showResultDelay).then(() => {
            let data = response.data[key.pub];

            if (data === undefined) {
                return;
            }

            addValuesToWallet(
                key.wif,
                data.final_balance / 100000000,
                data.n_tx
            );
        });
    });
});


if (isOnFirstPage) {
    addresses = keys.slice(1).map(w => w.cpub).join('|');

    addValuesToWallet('5HpHagT65TZzG1PH3CSu63k8DbpvD8s5ip4nEB3kEsreAnchuDf', 0, 24);
} else {
    addresses = keys.map(w => w.cpub).join('|');
}


axios.get(apiBaseUrl + addresses).then(response => {
    keys.forEach(key => {
        sleepRandom(showResultDelay).then(() => {
            let data = response.data[key.cpub];

            if (data === undefined) {
                return;
            }

            addValuesToWallet(
                key.wif,
                data.final_balance / 100000000,
                data.n_tx
            );
        });
    });
});



function addValuesToWallet(wif, balance, txCount)
{
    const el = document.getElementById(wif);
    const balanceEl = el.querySelector('.wallet-balance');
    const txEl = el.querySelector('.wallet-tx');

    el.dataset.loaded = parseInt(el.dataset.loaded) + 1;

    txEl.dataset.tx = parseInt(txEl.dataset.tx) + txCount;

    renderTransactionCount(txEl);

    balanceEl.dataset.balance = parseFloat(balanceEl.dataset.balance) + balance;

    renderBalance(balanceEl);

    if (el.dataset.loaded === '2') {
        el.classList.remove('loading');

        if (balanceEl.dataset.balance !== '0') {
            el.classList.add('filled')
        } else {
            txEl.dataset.tx === '0'
                ? el.classList.add('empty')
                : el.classList.add('used');
        }
    }
}


function renderBalance(el)
{
    const balance = parseFloat(el.dataset.balance);

    el.innerHTML = balance + ' btc';
}


function renderTransactionCount(el)
{
    const txCount = parseInt(el.dataset.tx);

    const text = txCount > 99 ? '99+' : txCount;

    el.innerHTML = '(' + text + ' tx)';
}


function sleepRandom(maxMs)
{
    return new Promise(resolve => setTimeout(resolve, Math.random() * maxMs));
}
