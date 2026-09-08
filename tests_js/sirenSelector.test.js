import { getSirensToShow } from "../src_js/sirenSelector.js";

describe("getSirensToShow", () => {

    test("keeps original SIREN selected when the groups allow it", () => {

        const result = getSirensToShow(["123", "456"], "123");

        expect(result).toEqual([
            {
                siren: "123",
                selected: true,
                disabled: false
            },
            {
                siren: "456",
                selected: false,
                disabled: false
            }
        ]);
    });


    test("adds original SIREN disabled when no longer allowed", () => {

        const result = getSirensToShow(["456", "789"], "123");

        expect(result).toContainEqual({
            siren: "123 (hors groupe)",
            selected: true,
            disabled: true
        });
    });

    test("only shows available sirens when no siren is selected", () => {

        const result = getSirensToShow(["456"], "");

        expect(result).toEqual(
            [
                {
                    siren: "456",
                    selected: false,
                    disabled: false
                }
    ]);
    }
    )
});
