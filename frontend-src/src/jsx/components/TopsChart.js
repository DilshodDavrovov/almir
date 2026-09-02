import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  BarElement,
  Legend,
  PolarAreaController, 
  RadialLinearScale, 
  ArcElement
} from "chart.js";
import React from "react";
import { PolarArea } from "react-chartjs-2";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  BarElement,
  Legend,
  PolarAreaController, 
  RadialLinearScale, 
  ArcElement
);
const TopsChart = (props) => {
  const { arr } = props;
  const data = {
    labels: arr.map(key => key.name),
    datasets: [
      {
        data: arr.map(key => key.USD),
        borderWidth: 0,
        backgroundColor: [
          "#84e8e5",
          "#b183e0",
          "#7adc00",
          "#84ff95",
          "#00a2ff",
        ],
      },
    ],
  };

  return <PolarArea className="home_polar_chart" data={data} options={{
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: false
    }
  }} />;

};

export default TopsChart;
