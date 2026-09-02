import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  BarElement,
  Legend,
  ArcElement
} from "chart.js";
import React from "react";
import { Doughnut } from "react-chartjs-2";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  BarElement,
  Legend,
  ArcElement
);

export const TOP_COLORS = ["#4361ee", "#0ea5a4", "#f59e0b", "#8b5cf6", "#ef4444", "#22c55e", "#0ea5e9"];

const TopsChart = ({ arr }) => {
  const data = {
    labels: arr.map((k) => k.name),
    datasets: [
      {
        data: arr.map((k) => k.USD),
        borderWidth: 2,
        borderColor: "#fff",
        hoverBorderColor: "#fff",
        backgroundColor: arr.map((_, i) => TOP_COLORS[i % TOP_COLORS.length]),
      },
    ],
  };
  return (
    <Doughnut
      className="home_polar_chart"
      data={data}
      options={{
        responsive: true,
        maintainAspectRatio: false,
        cutout: "68%",
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => ` ${ctx.label}: ${arr[ctx.dataIndex] ? arr[ctx.dataIndex].perc : ""} %`,
            },
          },
        },
      }}
    />
  );
};

export default TopsChart;
