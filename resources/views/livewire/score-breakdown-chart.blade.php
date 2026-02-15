<div>
    <div x-data="{
        chartData: @js($this->chartData),
        chartType: @js($this->chartType),
        chart: null,
        init() {
            if (typeof ApexCharts === 'undefined') return;

            const options = this.chartType === 'radar'
                ? this.radarOptions()
                : this.barOptions();

            this.chart = new ApexCharts(this.$refs.chart, options);
            this.chart.render();
        },
        barOptions() {
            return {
                chart: { type: 'bar', height: 300 },
                series: [
                    { name: 'Score', data: this.chartData.scores },
                    { name: 'Max', data: this.chartData.maxScores },
                ],
                xaxis: { categories: this.chartData.labels },
                plotOptions: {
                    bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 }
                },
                colors: ['#3b82f6', '#e5e7eb'],
            };
        },
        radarOptions() {
            return {
                chart: { type: 'radar', height: 300 },
                series: [
                    { name: 'Score', data: this.chartData.percentages },
                ],
                xaxis: { categories: this.chartData.labels },
                yaxis: { max: 100 },
                colors: ['#3b82f6'],
            };
        },
    }"
    >
        <div x-ref="chart" wire:ignore></div>
    </div>
</div>
