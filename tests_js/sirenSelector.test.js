import { getSirensToShow } from "../src_js/sirenSelector.js";

describe("getSirensToShow", () => {

    test("keeps original SIREN selected when it belongs to group", () => {

        const result = getSirensToShow(
            "1",
            "123",
            {
                "1": [
                    "123",
                    "456"
                ]
            }
        );

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


    test("adds original SIREN disabled when outside group", () => {

        const result = getSirensToShow(
            "2",
            "123",
            {
                "2": [
                    "456",
                    "789"
                ]
            }
        );

        expect(result).toContainEqual({
            siren: "123 (hors groupe)",
            selected: true,
            disabled: true
        });
    });

    test("only shows group's siren when no siren is selected", () => {

        const result = getSirensToShow(
            "1",
            "",
            {
                "1": [
                    "456"
                ]
            }
        );

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