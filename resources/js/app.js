import Chart from "chart.js/auto";
import ChartDataLabels from "chartjs-plugin-datalabels";

import Alpine from "alpinejs";
window.Chart = Chart;
window.Alpine = Alpine;

Chart.register(ChartDataLabels);

Alpine.start();
