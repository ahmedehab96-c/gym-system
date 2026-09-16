import { useCountUp } from "../../hooks/useCountUp";

export function Counter({ value, suffix = "", duration }: { value: number; suffix?: string; duration?: number }) {
  const { ref, value: current } = useCountUp(value, duration);
  return (
    <span ref={ref}>
      {current.toLocaleString()}
      {suffix}
    </span>
  );
}
