import {
  ResponsiveContainer,
  BarChart as Chart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  PieChart,
  Pie,
  Cell,
  Legend,
  LineChart,
  Line,
} from "recharts";
import { label } from "../lib/hooks";
const colors = [
  "#10677b",
  "#00a779",
  "#f8b647",
  "#8370d0",
  "#ef738b",
  "#adbccb",
];
const prepare = (data) =>
  data.map((d) => ({ ...d, name: label(d.name), value: Number(d.value) }));
export function BarChart({ data = [], line = false }) {
  if (!data.length) return <p className="empty">No data for this selection.</p>;
  const Component = line ? LineChart : Chart;
  return (
    <div style={{ height: 240, width: "100%", minWidth: 0 }}>
      <ResponsiveContainer>
        <Component
          data={prepare(data)}
          margin={{ top: 15, right: 15, left: -15, bottom: 15 }}
        >
          <CartesianGrid
            strokeDasharray="3 3"
            vertical={false}
            stroke="#e4edf5"
          />
          <XAxis dataKey="name" tick={{ fontSize: 10 }} interval={0} />
          <YAxis tick={{ fontSize: 10 }} allowDecimals={false} />
          <Tooltip />
          {line ? (
            <Line
              type="monotone"
              dataKey="value"
              name="Letters"
              stroke="#0564c9"
              strokeWidth={2}
            />
          ) : (
            <Bar
              dataKey="value"
              name="Records"
              fill="#225c88"
              radius={[2, 2, 0, 0]}
            />
          )}
        </Component>
      </ResponsiveContainer>
    </div>
  );
}
export function DonutChart({ data = [] }) {
  if (!data.some((d) => Number(d.value) > 0))
    return <p className="empty">No data for this selection.</p>;
  return (
    <div style={{ height: 240, width: "100%", minWidth: 0 }}>
      <ResponsiveContainer>
        <PieChart>
          <Pie
            data={prepare(data)}
            dataKey="value"
            nameKey="name"
            innerRadius={55}
            outerRadius={85}
            paddingAngle={1}
          >
            {data.map((d, i) => (
              <Cell key={d.name} fill={colors[i % colors.length]} />
            ))}
          </Pie>
          <Tooltip />
          <Legend iconType="circle" wrapperStyle={{ fontSize: 11 }} />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
}
