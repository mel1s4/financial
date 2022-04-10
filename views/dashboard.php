<?php $current_user = wp_get_current_user(); ?>
<script src="https://unpkg.com/vue@3"></script>

<main id="viroz-financial-dashborad">
  <header class="financial-dashboard__header">
    <h1>
      <?php echo $current_user->display_name ?>
    </h1>
  </header>
  <section class="add-transaction">
    <header class="header">
      <h2> Add Transaction </h2>
    </header>
    <div class="add-transaction__form">
      <div class="input-gropup --number-signs">
        <button @click="quantity = -1 * Math.abs(quantity)"
                v-bind:class="{'--active':quantity<0}"
                class="--subtract">
          -
        </button>
        <button @click="quantity = Math.abs(quantity)"
                v-bind:class="{'--active':quantity>0}"
                class="--add">
          +
        </button>
      </div>
      <div class="input-group">
        <label>
          Quantity
        </label>
        <input type="number" v-model="quantity" pattern="[0-9]*" inputmode="numeric">
      </div>
      <div class="input-group">
        <label>
          Notes
        </label>
        <textarea name="notes" v-model="notes"></textarea>
      </div>
      <div class="input-group">
        <label>
          Wallet
        </label>
        <input type="text" v-model="wallet" maxlength="4">
        <ul class="wallet-options">
          <li class="wallet-option-item" v-for="option in wallets">
            <label  @click="wallet = option.wallet">
              <span class="name" v-html="option.wallet" v-bind:class="{'--active':wallet == option.wallet}">
              </span>
            </label>
          </li>
        </ul>
      </div>
      <div class="input-group" v-if="!repeats && backdate">
        <label>
          Date
        </label>
        <input type="date" v-model="date">
      </div>
      <fieldset class="recurrance" v-if="repeats && !backdate">
        <div class="input-group">
          <label>
            Recurrance Quantity
          </label>
          <input type="number" v-model="recurranceQuantity">
        </div>
        <div class="input-group">
          <label>
            Recurrance Unit
          </label>
          <select name="action" v-model="recurranceUnit">
            <option value="days" selected='selected'> Days </option>
            <option value="weeks"> Weeks </option>
            <option value="months"> Months </option>
            <option value="years"> Years </option>
          </select>
        </div>
        <div class="input-group">
          <label>
            Start Date
          </label>
          <input type="date" v-model="recurranceStart">
        </div>
        <div class="input-group">
          <label>
            End Date
          </label>
          <input type="date" v-model="recurranceEnd">
        </div>
      </fieldset>
    </div>
    <div class="actions">
      <label v-if="!repeats">
        <input type="checkbox" v-model="backdate" name="special_action">
        Backdate
      </label>
      <label v-if="!backdate">
        <input type="checkbox" v-model="repeats" name="special_action">
        Repeat
      </label>
      <button class="button button-primary" @click="addTransaction" v-bind:disabled="addTransactionLoading">
        Add
      </button>
    </div>
  </section>
  <section class="wallets">
    <h2> Wallet Ballance </h2>
    <ul class="wallets-list">
      <li class="wallet-item" v-for="wallet in wallets">
        <p class="name" v-html="wallet.wallet">
        </p>
        <p class="balance" v-html="currencyFormat(balance[wallet.wallet])">
        </p>
      </li>
      <li class="wallet-item --total">
        <p class="name">
          Total
        </p>
        <p class="balance" v-html="currencyFormat(total)">
        </p>
      </li>
    </ul>
  </section>
  <section class="next-transactions">
    <h2>
      Recurring Transactions
    </h2>
    <ul class="next-transactions-list">
      <li class="next-transaction__wrapper" v-for="transaction in nextTransactions">
        <article class="next-transaction">
          <div class="content">
            <p class="quantity" v-html="currencyFormat(transaction.quantity)"
            v-bind:class="{'--negative': transaction.quantity < 0}">
            </p>
            <p class="notes"
              v-html="transaction.notes">
            </p>
            <p class="next-payment">
              Next payment: {{ nextPayment(transaction) }}
            </p>
            <p class="repeats">
              Repeats every ({{ meta(transaction).recurrance_quantity }}) {{ meta(transaction).recurrance_unit }} since {{ meta(transaction).recurrance_start }}.
            </p>
          </div>
          <p class="next-transaction__actions">
            <button class="button --delete" @click="deleteTransaction(transaction.id, 'future')"
            v-bind:disabled="isDeleting(transaction.id)">
              Delete
            </button>
            <button class="button --create">
              Pay Now
            </button>
          </p>
        </article>
      </li>
    </ul>
  </section>
  <section id="graph">
    <header class="graph__header">
      <h2 class="title"> Balance Graph </h2>
    </header>
    <div class="graph__params">
      <div class="input-group">
        <label>
          Wallets
        </label>
        <ul class="wallet-options">
          <li v-for="option in wallets"
              class="wallet-option-item">
            <label>
              <input type="checkbox" v-bind:value="option.wallet" v-model="graphWallets">
                <span class="name" v-html="option.wallet">
              </span>
            </label>
          </li>
        </ul>
      </div>
      <div class="input-group">
        <label>
          Graph Unit
        </label>
        <select v-model="graphUnit">
          <option value="days" selected='selected'> Days </option>
          <option value="weeks"> Weeks </option>
          <option value="months"> Months </option>
          <option value="years"> Years </option>
        </select>
      </div>
      <div class="input-group">
        <label>
          Start Date
        </label>
        <input type="date" v-model="graphStart">
      </div>
      <div class="input-group">
        <label>
          End Date
        </label>
        <input type="date" v-model="graphEnd">
      </div>
      <div class="button-wrapper">
        <label>
          <input type="checkbox" v-model="accumulativeGraph">
          Accumulative
        </label>
        <button class="button" @click="getGraphBalances()">
          Graph
        </button>
      </div>
    </div>
    <div class="graph__wrapper">
      <ul class="graph-balances">
        <li class="graph-balance"
            v-for="balance in graphBalances.balances"
            v-bind:class="{'--today' : isToday(balance.date)}">
          <span class="date" v-html="dateFormat(balance.date)">
          </span>
          <span class="balance" v-html="returnGraphBalance(balance)">
          </span>
          <span class="graph-unit-line"
          v-bind:style="returnGraphBalanceStyle(balance)">
          </span>
        </li>
      </ul>
    </div>
  </section>
  <section class="transaction-history">
    <header class="header">
      <h2> Transaction History </h2>
    </header>
    <div class="table__wrapper">
      <table>
        <thead>
          <tr>
            <th class="date">
              Date
            </th>
            <th class="quantity">
              Quantity
            </th>
            <th class="wallet">
              Wallet
            </th>
            <th class="notes">
              Notes
            </th>
            <th class="delete">
              Delete
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="transaction in transactions">
            <td class="date">
              <p v-html="timeSince(transaction.timestamp)">
              </p>
            </td>
            <td class="quantity">
              <p v-html="transaction.quantity">
              </p>
            </td>
            <td class="wallet">
              <p v-html="transaction.wallet">
              </p>
            </td>
            <td class="notes">
              <p v-html="transaction.notes">
              </p>
            </td>
            <td class="delete">
              <button class="button --delete" @click="deleteTransaction(transaction.id, 'history')">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
  Vue.createApp({
    data() {
      return {
        quantity: '',
        wallet: 'MAIN',
        repeats: false,
        recurranceQuantity: 1,
        recurranceUnit: 'days',
        recurranceStart: '',
        recurranceEnd: '',
        backdate: false,
        date: '',
        addTransactionLoading: false,
        transactions: [],
        wallets: [],
        balance: [],
        notes: '',
        total: 0,
        graphUnit: 'days',
        graphEnd: '',
        graphStart: '',
        graphBalances: [],
        graphWallets: [],
        accumulativeGraph: false,
        nextTransactions: [],
        delete: [],
      };
    },
		methods: {
      isToday(date_str) {
        date = new Date(date_str);
        today = new Date();
        return date.getDate() == today.getDate() &&
          date.getMonth() == today.getMonth() &&
          date.getFullYear() == today.getFullYear();
      },
      returnGraphBalanceStyle(balance) {
        let total = balance.balance;
        if(this.accumulativeGraph) {
          total = this.returnGraphBalance(balance, false);
        }
        return 'height:'+(total * 100 / this.graphBalances.max_value) + '%';
      },
      returnGraphBalance(reference, format = true) {
        var total = 0;
        if(this.accumulativeGraph) {
          for (var i = 0; i < this.graphBalances.balances.length; i++) {
            timestamp = this.graphBalances.balances[i].date;
            if(timestamp <= reference.date) {
              total += parseInt(this.graphBalances.balances[i].balance);
            }
          }
          if(format) {
            return this.currencyFormat(total);
          }
          return total;
        }
        return this.currencyFormat(reference.balance);
      },
      getNextTransactions() {
        params = {
          operation: 'get_next_transactions',
        };
        this.ajaxFunction(params, (response) => {
          if(response !== 'loading') {
            response = JSON.parse(response.responseText);
            console.log(response);
            this.nextTransactions = response;
          }
        });
      },
      getWalletBalance(wallet) {
        params = {
          operation: 'get_balance',
          wallet: wallet.wallet,
        };
        this.ajaxFunction(params, (response) => {
          if(response !== 'loading') {
            response = JSON.parse(response.responseText);
            this.balance[wallet.wallet] = response[0].balance;
            this.total += parseInt(response[0].balance);
          }
        });
      },
      getWalletsBalance() {
        if(this.wallets.length) {
          this.wallets.forEach((wallet) => {
            this.getWalletBalance(wallet);
          });
        }
      },
      async getTransactions() {
        params = {
          operation: 'get_transactions',
        };
        await this.ajaxFunction(params, (response) => {
          if(response !== 'loading') {
            this.transactions = JSON.parse(response.responseText);
          }
        });
      },
      async getGraphBalances() {
        params = {
          operation: 'get_graph_balances',
          unit: this.graphUnit,
          end: this.graphEnd,
          start: this.graphStart,
          wallets: this.graphWallets,
        };
        await this.ajaxFunction(params, (response) => {
          if(response !== 'loading') {
            console.log(response.responseText);
            this.graphBalances = JSON.parse(response.responseText);
          }
        });
      },
      isDeleting(transaction) {
        return this.delete.includes(transaction);
      },
      async deleteTransaction(transaction, refresh) {
        this.delete.push(transaction);
        params = {
          operation: 'delete_transaction',
          transaction: transaction,
        };
        await this.ajaxFunction(params, (response) => {
          if(response !== 'loading') {
            if(refresh == 'history') {
              this.getTransactions();
            } else {
              this.getNextTransactions();
            }
          }
        });
      },
      getWallets() {
        params = {
          operation: 'get_wallets',
        };
        this.ajaxFunction(params, (response) => {
          if(response !== 'loading') {
            this.wallets = JSON.parse(response.responseText);
            this.getWalletsBalance();
          }
        });
      },
      nextPayment(transaction) {
        var meta = JSON.parse(transaction.meta);
        var start = new Date(meta.recurrance_start);
        var end = new Date(meta.recurrance_end);
        var date = new Date();
        var next = start;
        var quantityInDays = 1;
        if(meta.recurrance_unit == 'weeks') {
          quantityInDays = 7;
        } else if(meta.recurrance_unit == 'months') {
          quantityInDays = 30;
        } else if(meta.recurrance_unit == 'years') {
          quantityInDays = 365;
        }
        var quantity = meta.recurrance_quantity * quantityInDays;
        while(next < end) {
          if(next.getTime() >= date.getTime()) {
            return this.formatDate(next);
          }
          next.setDate(next.getDate() + quantity);
        }
      },
      currencyFormat(num) {
        return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
      },
      formatDate(datestring) {
        var date = new Date(datestring);
        var monthNames = "Enero,Febrero,Marzo,Abril,Mayo,Junio,Agosto,Septiembre,Octubre,Noviembre,Diciembre".split(",");
        // format date
        var day = date.getDate();
        var month = date.getMonth() + 1;
        var year = date.getFullYear();

        // return formatted date
        return day + ' ' + monthNames[month - 1] + ' ' + year;
      },
      addTransaction() {
        if(this.quantity == 0) {
          return;
        }
        params = {
          operation: 'add_transaction',
          quantity: this.quantity,
          wallet: this.wallet,
          repeats: this.repeats,
          recurranceQuantity: this.recurranceQuantity,
          recurranceUnit: this.recurranceUnit,
          recurranceStart: this.recurranceStart,
          recurranceEnd: this.recurranceEnd,
          backdate: this.backdate,
          date: this.date,
          notes: this.notes,
        };
        this.ajaxFunction(params, (response) => {
          if(response == 'loading') {
            this.addTransactionLoading = true;
          }
          if(response.responseText) {
            const response_json = JSON.parse(response.responseText);
            this.addTransactionLoading = false;
            this.getTransactions();
            this.getNextTransactions();
            if (this.wallets.filter(e => e.wallet === response_json.wallet).length == 0) {
              this.wallets.push( response_json );
            }
            this.getWalletBalance(response_json);
          }
        });
      },
      ajaxFunction(data, callback) {
        callback('loading');
        data.action = 'viroz_financial_ajax_api';
				var params = Object.keys(data).map((key) => {
					return encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
				}).join('&');
				var xhttp = new XMLHttpRequest();
				xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						callback(this);
					}
				};
				xhttp.open('POST', '/wp-admin/admin-ajax.php', true);
				xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xhttp.send( params );
      },
      timeSince(string_timestamp) {
        var date = new Date();
        var now_utc =  Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(),
        date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds());

        var t_date = new Date(string_timestamp + ' UTC');
        var diff = Math.abs(Math.floor((new Date(now_utc) - t_date) / 1000));
        if(diff < 30) {
          return 'Just now';
        }
        if(diff > 604800) {
          return t_date.toLocaleDateString();
        }
        // calculate (and subtract) whole days
        days = Math.floor(diff / 86400);
        diff = diff%86400;

        hours = Math.floor(diff / 3600);
        diff = diff%3600;

        minutes = Math.floor(diff / 60);
        seconds = diff%60;

        if (days > 0) {
          return days + ' days ago';
        }
        if (hours > 0) {
          return hours + ':' + this.prefixCero(minutes) + ' hours ago';
        }
        if (minutes > 0) {
          return minutes + ':' + this.prefixCero(seconds) + ' minutes ago';
        }
        return seconds + ' seconds ago';
      },
      dateFormat(string_timestamp) {
        var date = new Date(string_timestamp + ' UTC');
        return date.toLocaleDateString();
      },
      prefixCero(num) {
        if(num < 10) {
          return '0' + '' + num;
        }
        return num;
      },
      meta(transaction) {
        return JSON.parse(transaction.meta);
      }
		},
		async mounted() {
      var today = new Date();
      var daysAgo = new Date();
      var inDays = new Date();
      daysAgo.setDate(today.getDate() - 7);
      inDays.setDate(today.getDate() + 7);

      this.graphEnd = inDays.getFullYear() + '-' + this.prefixCero(inDays.getMonth() + 1) + '-' + this.prefixCero(inDays.getDate());

      this.graphStart = daysAgo.getFullYear() + '-' + this.prefixCero(daysAgo.getMonth() + 1) + '-' + this.prefixCero(daysAgo.getDate());

      await this.getTransactions();
      await this.getWallets();
      await this.getGraphBalances();
      await this.getNextTransactions();
		},
  }).mount('#viroz-financial-dashborad');
</script>