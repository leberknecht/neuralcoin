const HighRaise = item => {
    let baseCurrency = item.symbolName.match(/^(BTC|USDT|ETH|XMR|BNB|ETC)/)[0];
    let symbolName = item.symbolName.replace(/^(BTC|USDT|ETH|XMR|BNB|ETC)/, "");

    let exchange = document.querySelectorAll('#exchange-name')[0].value;
    let urls = {
        'bittrex': 'https://bittrex.com/Market/Index?MarketName='+ baseCurrency + '-' + symbolName,
        'poloniex': 'https://poloniex.com/exchange#'+ baseCurrency + '_' + symbolName,
        'binance': 'https://www.binance.com/trade.html?symbol=' + symbolName + '_' + baseCurrency
    };

    return (
        <tr>
            <td>
                <a className="high-raise-symbol" href={urls[exchange]}>
                    {symbolName}
                </a>
            </td>
            <td> {item.old.price}  </td>
            <td> {item.current.price}  </td>
            <td> {new String((((item.current.price / item.old.price) - 1) * 100)).substring(0,5)}  </td>
        </tr>
    );
};

class KnownSymbolsList extends React.Component {

    constructor(props) {
        super(props);

        this.refreshHighRaises = this.refreshHighRaises.bind(this);

        const knownElementsContainer = document.querySelectorAll('#known-exchanges span');
        const knownExchanges = [];
        for (let x = 0; x < knownElementsContainer.length; x++) {
            knownExchanges.push(knownElementsContainer[x].innerText)
        }

        this.state = {
            highRaises: [],
            highRaiseUpdateUrl: document.getElementById('high-raise-update-url').getAttribute('data-value'),
            knownExchanges: knownExchanges
        };
    }

    refreshHighRaises() {
        let component = this;
        let exchangeName = this.exchangeNameSelect.value;
        let timeScope = this.timeScope.value;
        let raiseLimit = this.raiseLimit.value;

        let queryString = '?exchangeName=' + exchangeName + '&timeScope=' + timeScope + '&raiseLimit=' + raiseLimit;
        axios.get(this.state.highRaiseUpdateUrl + queryString)
    .then(res => {
            const highRaiseSymbols = res.data;
            component.setState({ highRaises: highRaiseSymbols });
        });
    }

    componentDidMount() {
        this.refreshHighRaises();
        this.interval = setInterval(this.refreshHighRaises, 10000)
    }

    componentWillUnmount() {
        clearInterval(this.interval);
    }

    render() {
        const highRaiseItems = [];
        this.state.highRaises.forEach((entry, index) => {
            highRaiseItems.push(
                <HighRaise
                    symbolName={entry.old.symbol.name}
                    old={entry.old}
                    current={entry.current}
                    key={"entry" + index}
                />
            )
        });
        const exchangeOptions = [];
        this.state.knownExchanges.forEach((exchangeName) => {
            exchangeOptions.push(<option value={exchangeName} key={exchangeName}>{exchangeName}</option>)
        });

        return (
            <form>
                <h2>High Raises</h2>
                <div className="high-raise-options row">
                    <div className="col-md-4 form-group">
                        <label htmlFor="exchange-name">Exchange:</label>
                        <select className="form-control" defaultValue="bittrex" ref={(select) => { this.exchangeNameSelect = select; }}  id="exchange-name">
                            {exchangeOptions}
                        </select>
                    </div>
                    <div className="col-md-4">
                        <label htmlFor="timescope">Time-Scope</label>
                        <input className="form-control" ref={(input) => { this.timeScope = input; }} name="timescope" id="timescope" defaultValue="-10 minutes" />
                    </div>
                    <div className="col-md-4">
                        <label htmlFor="raise-limit">Raise-limit (5%)</label>
                        <input className="form-control range-input" data-label-template="Raise-limit ({value} %)" type="range" min={1} max={8} ref={(input) => { this.raiseLimit = input; }} name="raise-limit" id="raise-limit" defaultValue="5" />
                    </div>
                </div>
                <table className="table">
                    <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Old Price</th>
                        <th>Current Price</th>
                        <th>Change %</th>
                    </tr>
                    </thead>
                    <tbody>
                    {highRaiseItems}
                    </tbody>
                </table>
            </form>
        );
    }
}

ReactDOM.render(
    <KnownSymbolsList/>,
    document.getElementById('root')
);