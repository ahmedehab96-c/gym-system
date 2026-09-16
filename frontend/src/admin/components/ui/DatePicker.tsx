import { Calendar as CalendarIcon } from "lucide-react";
import { Input } from "./Input";

interface DatePickerProps {
  value: string;
  onChange: (v: string) => void;
  label?: string;
}

export function DatePicker({ value, onChange, label }: DatePickerProps) {
  return (
    <Input
      type="date"
      label={label}
      icon={<CalendarIcon size={15} />}
      value={value}
      onChange={(e) => onChange(e.target.value)}
    />
  );
}
