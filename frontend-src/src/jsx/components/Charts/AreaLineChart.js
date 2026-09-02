import React from 'react';
import { Line } from 'react-chartjs-2';

const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "right",
      labels: {
        usePointStyle: true,
        padding: 15
      }
    },
  }
};

export default function AreaLineChart(props) {
  const { data } = props
  return <Line options={options} data={data} />;
}
