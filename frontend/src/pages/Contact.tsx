import { type FormEvent, useState } from "react";
import { motion } from "framer-motion";
import { CheckCircle2, Loader2, Mail, MapPin, Phone } from "lucide-react";
import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { Reveal } from "../components/ui/Reveal";
import { Button } from "../components/ui/Button";
import { gymService } from "../services/gymService";
import { IMG, unsplash } from "../data/images";
import { FacebookIcon, InstagramIcon, TwitterIcon } from "../components/ui/SocialIcons";

const infoItems = [
  { icon: MapPin, label: "Address", value: "128 Iron District, Downtown Metro" },
  { icon: Phone, label: "Phone", value: "+1 (555) 210-8842" },
  { icon: Mail, label: "Email", value: "hello@apexclub.com" },
];

const hours = [
  { day: "Monday – Friday", time: "05:00 – 23:00" },
  { day: "Saturday", time: "06:00 – 22:00" },
  { day: "Sunday", time: "07:00 – 20:00" },
];

type Status = "idle" | "submitting" | "success";

export default function Contact() {
  const [status, setStatus] = useState<Status>("idle");

  async function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setStatus("submitting");
    const formData = new FormData(e.currentTarget);
    await gymService.submitContactForm(Object.fromEntries(formData.entries()) as Record<string, string>);
    setStatus("success");
  }

  return (
    <>
      <PageHero
        eyebrow="Contact"
        title="Let's Get You Started"
        description="Have a question, or ready to book a tour? Reach out — our team responds within one business day."
        image={unsplash(IMG.barbellGroupClassBW, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container className="grid grid-cols-1 gap-16 lg:grid-cols-5">
          <Reveal className="lg:col-span-3">
            <div className="glass rounded-3xl p-8 sm:p-10">
              {status === "success" ? (
                <motion.div
                  initial={{ opacity: 0, scale: 0.95 }}
                  animate={{ opacity: 1, scale: 1 }}
                  className="flex flex-col items-center justify-center gap-4 py-16 text-center"
                >
                  <CheckCircle2 size={44} className="text-gold-400" />
                  <h3 className="font-display text-2xl text-white">Message Sent</h3>
                  <p className="max-w-sm text-sm text-white/55">
                    Thanks for reaching out — this is a frontend demo, so no message was actually sent, but our team would normally respond within one business day.
                  </p>
                  <Button variant="outline" size="sm" onClick={() => setStatus("idle")}>
                    Send Another
                  </Button>
                </motion.div>
              ) : (
                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                  <Field label="Full Name" name="name" placeholder="John Doe" required />
                  <Field label="Email Address" name="email" type="email" placeholder="john@example.com" required />
                  <Field label="Phone Number" name="phone" placeholder="+1 (555) 000-0000" className="sm:col-span-2" />
                  <Field label="Subject" name="subject" placeholder="Membership inquiry" className="sm:col-span-2" />
                  <div className="flex flex-col gap-2 sm:col-span-2">
                    <label htmlFor="message" className="text-xs font-semibold uppercase tracking-widest text-white/50">
                      Message
                    </label>
                    <textarea
                      id="message"
                      name="message"
                      rows={5}
                      required
                      placeholder="Tell us about your goals..."
                      className="resize-none rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-white/30 outline-none transition-colors focus:border-gold-400"
                    />
                  </div>
                  <div className="sm:col-span-2">
                    <Button type="submit" className="w-full sm:w-auto" disabled={status === "submitting"}>
                      {status === "submitting" ? (
                        <span className="flex items-center gap-2">
                          <Loader2 size={16} className="animate-spin" /> Sending...
                        </span>
                      ) : (
                        "Send Message"
                      )}
                    </Button>
                  </div>
                </form>
              )}
            </div>
          </Reveal>

          <Reveal delay={0.1} className="lg:col-span-2">
            <div className="flex flex-col gap-8">
              <div className="flex flex-col gap-5">
                {infoItems.map((item) => (
                  <div key={item.label} className="flex items-start gap-4">
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gold-400/10 text-gold-300">
                      <item.icon size={18} />
                    </span>
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-widest text-white/55">{item.label}</p>
                      <p className="text-sm text-white/75">{item.value}</p>
                    </div>
                  </div>
                ))}
              </div>

              <div className="glass rounded-2xl p-6">
                <p className="mb-4 text-xs font-semibold uppercase tracking-widest text-white/55">Opening Hours</p>
                <ul className="flex flex-col gap-2.5">
                  {hours.map((h) => (
                    <li key={h.day} className="flex justify-between text-sm text-white/65">
                      <span>{h.day}</span>
                      <span className="text-white/85">{h.time}</span>
                    </li>
                  ))}
                </ul>
              </div>

              <div className="flex gap-3">
                {[
                  { Icon: InstagramIcon, label: "Instagram" },
                  { Icon: FacebookIcon, label: "Facebook" },
                  { Icon: TwitterIcon, label: "Twitter" },
                ].map(({ Icon, label }) => (
                  <a
                    key={label}
                    href="#"
                    aria-label={label}
                    className="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 text-white/70 transition-colors hover:border-gold-400 hover:text-gold-300"
                  >
                    <Icon size={16} />
                  </a>
                ))}
              </div>

              <div className="relative h-56 overflow-hidden rounded-2xl border border-white/10">
                <div className="flex h-full w-full items-center justify-center bg-surface-2 text-sm text-white/50">
                  Map placeholder — Google Maps embed
                </div>
              </div>
            </div>
          </Reveal>
        </Container>
      </section>
    </>
  );
}

function Field({
  label,
  name,
  type = "text",
  placeholder,
  required,
  className,
}: {
  label: string;
  name: string;
  type?: string;
  placeholder?: string;
  required?: boolean;
  className?: string;
}) {
  return (
    <div className={`flex flex-col gap-2 ${className ?? ""}`}>
      <label htmlFor={name} className="text-xs font-semibold uppercase tracking-widest text-white/50">
        {label}
      </label>
      <input
        id={name}
        name={name}
        type={type}
        placeholder={placeholder}
        required={required}
        className="rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-white/30 outline-none transition-colors focus:border-gold-400"
      />
    </div>
  );
}
