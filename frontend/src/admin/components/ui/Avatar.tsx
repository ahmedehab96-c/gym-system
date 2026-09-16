import { initials } from "../../utils/format";
import { cn } from "../../../utils/cn";

interface AvatarProps {
  src?: string;
  name: string;
  size?: "sm" | "md" | "lg" | "xl";
  className?: string;
}

const sizeMap = { sm: "h-8 w-8 text-xs", md: "h-10 w-10 text-sm", lg: "h-14 w-14 text-base", xl: "h-24 w-24 text-2xl" };

export function Avatar({ src, name, size = "md", className }: AvatarProps) {
  if (src) {
    return (
      <img
        src={src}
        alt={name}
        className={cn("shrink-0 rounded-full object-cover ring-2 ring-a-surface dark:ring-a-dark-surface", sizeMap[size], className)}
      />
    );
  }
  return (
    <div
      className={cn(
        "flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-a-accent/30 to-a-accent-2/30 font-semibold text-a-accent-2 dark:text-a-accent",
        sizeMap[size],
        className,
      )}
    >
      {initials(name)}
    </div>
  );
}
