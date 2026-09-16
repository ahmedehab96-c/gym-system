import { Filter } from "lucide-react";
import { Select } from "./Select";

interface FilterDropdownProps {
  label: string;
  value: string;
  onChange: (v: string) => void;
  options: { label: string; value: string }[];
}

export function FilterDropdown({ label, value, onChange, options }: FilterDropdownProps) {
  return (
    <div className="min-w-[160px]">
      <Select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        options={[{ label: `All ${label}`, value: "all" }, ...options]}
      />
    </div>
  );
}

export function FilterIcon() {
  return <Filter size={16} />;
}
